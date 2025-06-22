<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPCContainerBundle\DependencyInjection\JsonRpcProcedureCompilerPass;
use Tourze\JsonRPCContainerBundle\Tests\Fixtures\TestJsonRpcMethod;

/**
 * JsonRpcProcedureCompilerPass单元测试
 */
class JsonRpcProcedureCompilerPassTest extends TestCase
{
    private JsonRpcProcedureCompilerPass $compilerPass;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->compilerPass = new JsonRpcProcedureCompilerPass();
        $this->container = new ContainerBuilder();
    }

    /**
     * 测试process方法处理流程 - 标记的服务被正确处理
     */
    public function testProcess_withTaggedService_shouldRegisterMethods(): void
    {
        // 准备测试数据
        $methodName = 'test.method';
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
        $this->assertInstanceOf(Reference::class, $methodMapping[$methodName]);
        $this->assertEquals($serviceId, (string)$methodMapping[$methodName]);

        // 验证服务不是共享的
        $serviceDef = $this->container->getDefinition($serviceId);
        $this->assertFalse($serviceDef->isShared());
    }

    /**
     * 测试validateJsonRpcMethodTagAttributes - 缺少方法名属性
     */
    public function testValidateJsonRpcMethodTagAttributes_withoutMethodName_shouldThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service "test.service" is taggued as JSON-RPC method but does not have method name defined under "method" tag attribute key');

        $methodClass = new \ReflectionClass(JsonRpcProcedureCompilerPass::class);
        $validateMethod = $methodClass->getMethod('validateJsonRpcMethodTagAttributes');
        $validateMethod->setAccessible(true);

        $validateMethod->invokeArgs(null, ['test.service', []]);
    }

    /**
     * 测试validateJsonRpcMethodDefinition - 服务不实现JsonRpcMethodInterface
     */
    public function testValidateJsonRpcMethodDefinition_withInvalidClass_shouldThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service "test.service" is taggued as JSON-RPC method but does not implement');

        $methodClass = new \ReflectionClass(JsonRpcProcedureCompilerPass::class);
        $validateMethod = $methodClass->getMethod('validateJsonRpcMethodDefinition');
        $validateMethod->setAccessible(true);

        $invalidDef = new Definition(\stdClass::class);
        $validateMethod->invokeArgs(null, ['test.service', $invalidDef]);
    }

    /**
     * 测试validateJsonRpcMethodDefinition - 有效服务
     */
    public function testValidateJsonRpcMethodDefinition_withValidClass_shouldNotThrowException(): void
    {
        $methodClass = new \ReflectionClass(JsonRpcProcedureCompilerPass::class);
        $validateMethod = $methodClass->getMethod('validateJsonRpcMethodDefinition');
        $validateMethod->setAccessible(true);

        $validDef = new Definition(TestJsonRpcMethod::class);

        // 如果没有抛出异常，则测试通过
        $this->assertNull($validateMethod->invokeArgs(null, ['test.service', $validDef]));
    }

    /**
     * 测试findAndValidateJsonRpcMethodDefinition方法
     */
    public function testFindAndValidateJsonRpcMethodDefinition_withTaggedServices_shouldReturnMappings(): void
    {
        // 准备测试数据
        $methodName1 = 'test.method1';
        $serviceId1 = 'test.service1';
        $methodName2 = 'test.method2';
        $serviceId2 = 'test.service2';

        // 创建测试服务定义
        $serviceDefinition1 = new Definition(TestJsonRpcMethod::class);
        $serviceDefinition1->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName1
        ]);

        $serviceDefinition2 = new Definition(TestJsonRpcMethod::class);
        $serviceDefinition2->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
            JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $methodName2
        ]);

        // 添加到容器
        $this->container->setDefinition($serviceId1, $serviceDefinition1);
        $this->container->setDefinition($serviceId2, $serviceDefinition2);

        // 调用方法
        $result = JsonRpcProcedureCompilerPass::findAndValidateJsonRpcMethodDefinition($this->container);

        // 验证结果
        $this->assertArrayHasKey($serviceId1, $result);
        $this->assertArrayHasKey($serviceId2, $result);
        $this->assertContains($methodName1, $result[$serviceId1]);
        $this->assertContains($methodName2, $result[$serviceId2]);
    }
}
