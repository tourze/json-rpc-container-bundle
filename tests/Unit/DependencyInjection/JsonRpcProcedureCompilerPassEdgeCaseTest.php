<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPCContainerBundle\DependencyInjection\JsonRpcProcedureCompilerPass;
use Tourze\JsonRPCContainerBundle\Tests\Fixtures\TestJsonRpcMethod;

/**
 * JsonRpcProcedureCompilerPass边界情况和异常场景测试
 */
class JsonRpcProcedureCompilerPassEdgeCaseTest extends TestCase
{
    private JsonRpcProcedureCompilerPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new JsonRpcProcedureCompilerPass();
        $this->container = new ContainerBuilder();
    }

    /**
     * 测试空容器处理
     */
    public function testProcess_withEmptyContainer_shouldNotThrowException(): void
    {
        // 设置空的服务定位器定义
        $serviceLocatorDef = new Definition('Symfony\Component\DependencyInjection\ServiceLocator');
        $this->container->setDefinition('json_rpc_http_server.service_locator.method_resolver', $serviceLocatorDef);

        // 执行编译器通道应该不会抛出异常
        $this->compilerPass->process($this->container);

        // 验证服务定位器参数为空数组
        $methodResolverDef = $this->container->getDefinition('json_rpc_http_server.service_locator.method_resolver');
        $methodMapping = $methodResolverDef->getArgument(0);

        $this->assertIsArray($methodMapping);
        $this->assertEmpty($methodMapping);
    }

    /**
     * 测试单个服务多个方法标签处理
     */
    public function testProcess_withSingleServiceMultipleTags_shouldRegisterAllMethods(): void
    {
        $methodName1 = 'test.method1';
        $methodName2 = 'test.method2';
        $serviceId = 'test.service';

        // 创建测试服务定义，添加多个标签
        $serviceDefinition = new Definition(TestJsonRpcMethod::class);
        $serviceDefinition->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName1
        ]);
        $serviceDefinition->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName2
        ]);

        // 添加到容器
        $this->container->setDefinition($serviceId, $serviceDefinition);

        // 设置服务定位器定义
        $serviceLocatorDef = new Definition('Symfony\Component\DependencyInjection\ServiceLocator');
        $this->container->setDefinition('json_rpc_http_server.service_locator.method_resolver', $serviceLocatorDef);

        // 执行编译器通道
        $this->compilerPass->process($this->container);

        // 验证结果
        $methodResolverDef = $this->container->getDefinition('json_rpc_http_server.service_locator.method_resolver');
        $methodMapping = $methodResolverDef->getArgument(0);

        $this->assertArrayHasKey($methodName1, $methodMapping);
        $this->assertArrayHasKey($methodName2, $methodMapping);
        $this->assertEquals($serviceId, (string)$methodMapping[$methodName1]);
        $this->assertEquals($serviceId, (string)$methodMapping[$methodName2]);
    }

    /**
     * 测试多个服务相同方法名处理（后注册的覆盖先注册的）
     */
    public function testProcess_withDuplicateMethodNames_shouldUseLastRegistered(): void
    {
        $methodName = 'test.method';
        $serviceId1 = 'test.service1';
        $serviceId2 = 'test.service2';

        // 创建第一个测试服务定义
        $serviceDefinition1 = new Definition(TestJsonRpcMethod::class);
        $serviceDefinition1->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName
        ]);

        // 创建第二个测试服务定义
        $serviceDefinition2 = new Definition(TestJsonRpcMethod::class);
        $serviceDefinition2->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName
        ]);

        // 添加到容器
        $this->container->setDefinition($serviceId1, $serviceDefinition1);
        $this->container->setDefinition($serviceId2, $serviceDefinition2);

        // 设置服务定位器定义
        $serviceLocatorDef = new Definition('Symfony\Component\DependencyInjection\ServiceLocator');
        $this->container->setDefinition('json_rpc_http_server.service_locator.method_resolver', $serviceLocatorDef);

        // 执行编译器通道
        $this->compilerPass->process($this->container);

        // 验证结果 - 应该使用后注册的服务
        $methodResolverDef = $this->container->getDefinition('json_rpc_http_server.service_locator.method_resolver');
        $methodMapping = $methodResolverDef->getArgument(0);

        $this->assertArrayHasKey($methodName, $methodMapping);
        // 由于处理顺序，应该是最后一个被使用
        $this->assertEquals($serviceId2, (string)$methodMapping[$methodName]);
    }

    /**
     * 测试没有服务定位器定义的情况
     */
    public function testProcess_withoutServiceLocatorDefinition_shouldSkipGracefully(): void
    {
        $methodName = 'test.method';
        $serviceId = 'test.service';

        // 创建测试服务定义
        $serviceDefinition = new Definition(TestJsonRpcMethod::class);
        $serviceDefinition->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName
        ]);

        // 添加到容器，但不设置服务定位器定义
        $this->container->setDefinition($serviceId, $serviceDefinition);

        // 执行编译器通道，应该会失败但不应该是因为我们的逻辑
        $this->expectException(\Exception::class);
        $this->compilerPass->process($this->container);
    }

    /**
     * 测试特殊字符方法名的处理
     */
    public function testProcess_withSpecialCharacterMethodNames_shouldHandleCorrectly(): void
    {
        $methodNames = [
            'test@method',
            'test#method',
            'test$method',
            'test%method',
            'test^method',
            'test&method',
            'test*method',
            'test.method.with.dots',
            'test-method-with-dashes',
            'test_method_with_underscores',
        ];

        $serviceDefinitions = [];
        foreach ($methodNames as $index => $methodName) {
            $serviceId = "test.service.{$index}";
            $serviceDefinition = new Definition(TestJsonRpcMethod::class);
            $serviceDefinition->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
                JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName
            ]);
            $this->container->setDefinition($serviceId, $serviceDefinition);
            $serviceDefinitions[$methodName] = $serviceId;
        }

        // 设置服务定位器定义
        $serviceLocatorDef = new Definition('Symfony\Component\DependencyInjection\ServiceLocator');
        $this->container->setDefinition('json_rpc_http_server.service_locator.method_resolver', $serviceLocatorDef);

        // 执行编译器通道
        $this->compilerPass->process($this->container);

        // 验证结果
        $methodResolverDef = $this->container->getDefinition('json_rpc_http_server.service_locator.method_resolver');
        $methodMapping = $methodResolverDef->getArgument(0);

        foreach ($methodNames as $methodName) {
            $this->assertArrayHasKey($methodName, $methodMapping);
            $this->assertEquals($serviceDefinitions[$methodName], (string)$methodMapping[$methodName]);
        }
    }

    /**
     * 测试空字符串方法名（虽然会通过验证但不推荐）
     */
    public function testProcess_withEmptyStringMethodName_shouldRegisterEmptyKey(): void
    {
        $methodName = '';
        $serviceId = 'test.service';

        // 创建测试服务定义
        $serviceDefinition = new Definition(TestJsonRpcMethod::class);
        $serviceDefinition->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName
        ]);

        // 添加到容器
        $this->container->setDefinition($serviceId, $serviceDefinition);

        // 设置服务定位器定义
        $serviceLocatorDef = new Definition('Symfony\Component\DependencyInjection\ServiceLocator');
        $this->container->setDefinition('json_rpc_http_server.service_locator.method_resolver', $serviceLocatorDef);

        // 执行编译器通道
        $this->compilerPass->process($this->container);

        // 验证结果
        $methodResolverDef = $this->container->getDefinition('json_rpc_http_server.service_locator.method_resolver');
        $methodMapping = $methodResolverDef->getArgument(0);

        $this->assertArrayHasKey($methodName, $methodMapping);
        $this->assertEquals($serviceId, (string)$methodMapping[$methodName]);
    }

    /**
     * 测试非常长的方法名处理
     */
    public function testProcess_withVeryLongMethodName_shouldHandleCorrectly(): void
    {
        $methodName = str_repeat('a', 1000) . '.method';
        $serviceId = 'test.service';

        // 创建测试服务定义
        $serviceDefinition = new Definition(TestJsonRpcMethod::class);
        $serviceDefinition->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName
        ]);

        // 添加到容器
        $this->container->setDefinition($serviceId, $serviceDefinition);

        // 设置服务定位器定义
        $serviceLocatorDef = new Definition('Symfony\Component\DependencyInjection\ServiceLocator');
        $this->container->setDefinition('json_rpc_http_server.service_locator.method_resolver', $serviceLocatorDef);

        // 执行编译器通道
        $this->compilerPass->process($this->container);

        // 验证结果
        $methodResolverDef = $this->container->getDefinition('json_rpc_http_server.service_locator.method_resolver');
        $methodMapping = $methodResolverDef->getArgument(0);

        $this->assertArrayHasKey($methodName, $methodMapping);
        $this->assertEquals($serviceId, (string)$methodMapping[$methodName]);
    }

    /**
     * 测试findAndValidateJsonRpcMethodDefinition方法 - 空容器
     */
    public function testFindAndValidateJsonRpcMethodDefinition_withEmptyContainer_shouldReturnEmptyArray(): void
    {
        // 调用方法
        $result = JsonRpcProcedureCompilerPass::findAndValidateJsonRpcMethodDefinition($this->container);

        // 验证结果
        $this->assertEmpty($result);
    }
} 