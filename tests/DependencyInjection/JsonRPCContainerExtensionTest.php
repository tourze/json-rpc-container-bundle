<?php

namespace Tourze\JsonRPCContainerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\JsonRPCContainerBundle\DependencyInjection\JsonRPCContainerExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * JsonRPCContainerExtension单元测试
 *
 * @internal
 */
#[CoversClass(JsonRPCContainerExtension::class)]
final class JsonRPCContainerExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private JsonRPCContainerExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new JsonRPCContainerExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    /**
     * 测试load方法是否正确加载服务配置
     */
    public function testLoadShouldLoadServices(): void
    {
        // 调用load方法
        $this->extension->load([], $this->container);

        // 验证是否加载了核心服务
        $this->assertTrue($this->container->hasDefinition('json_rpc_http_server.service_locator.method_resolver'));
        $this->assertTrue($this->container->hasAlias('Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface'));

        // 验证服务别名是公开的
        $alias = $this->container->getAlias('Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface');
        $this->assertTrue($alias->isPublic());

        // 验证方法解析器服务定义
        $this->assertTrue($this->container->hasDefinition('Tourze\JsonRPCContainerBundle\Service\MethodResolver'));
    }

    /**
     * 测试load方法使用不同的配置参数
     */
    public function testLoadWithDifferentConfigsShouldLoadCorrectly(): void
    {
        $configs = [
            ['some_config' => 'value1'],
            ['other_config' => 'value2'],
        ];

        // 调用load方法 - 虽然当前实现忽略配置，但应该正常工作
        $this->extension->load($configs, $this->container);

        // 验证服务仍然被正确加载
        $this->assertTrue($this->container->hasDefinition('json_rpc_http_server.service_locator.method_resolver'));
        $this->assertTrue($this->container->hasAlias('Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface'));
    }

    /**
     * 测试load方法使用空配置数组
     */
    public function testLoadWithEmptyConfigsShouldLoadCorrectly(): void
    {
        // 调用load方法
        $this->extension->load([], $this->container);

        // 验证服务被正确加载
        $this->assertTrue($this->container->hasDefinition('json_rpc_http_server.service_locator.method_resolver'));
        $this->assertTrue($this->container->hasAlias('Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface'));
        $this->assertTrue($this->container->hasDefinition('Tourze\JsonRPCContainerBundle\Service\MethodResolver'));
    }

    /**
     * 测试多次调用load方法
     */
    public function testLoadCalledMultipleTimesShouldNotCreateDuplicates(): void
    {
        // 第一次调用
        $this->extension->load([], $this->container);
        $firstCallDefinitionCount = count($this->container->getDefinitions());

        // 第二次调用
        $this->extension->load([], $this->container);
        $secondCallDefinitionCount = count($this->container->getDefinitions());

        // 验证定义数量没有增加（因为相同的定义会被覆盖）
        $this->assertEquals($firstCallDefinitionCount, $secondCallDefinitionCount);

        // 验证关键服务仍然存在
        $this->assertTrue($this->container->hasDefinition('json_rpc_http_server.service_locator.method_resolver'));
        $this->assertTrue($this->container->hasAlias('Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface'));
    }

    /**
     * 测试加载的服务定位器定义的正确性
     */
    public function testLoadServiceLocatorDefinitionShouldHaveCorrectConfiguration(): void
    {
        // 调用load方法
        $this->extension->load([], $this->container);

        // 获取服务定位器定义
        $serviceLocatorDef = $this->container->getDefinition('json_rpc_http_server.service_locator.method_resolver');

        // 验证定义的类名
        $this->assertEquals('Symfony\Component\DependencyInjection\ServiceLocator', $serviceLocatorDef->getClass());

        // 验证标签
        $tags = $serviceLocatorDef->getTags();
        $this->assertArrayHasKey('container.service_locator', $tags);

        // 验证参数结构（应该有一个空数组作为第一个参数）
        $arguments = $serviceLocatorDef->getArguments();
        $this->assertCount(1, $arguments);
        $this->assertEquals([], $arguments[0]);
    }

    /**
     * 测试方法解析器别名的正确性
     */
    public function testLoadMethodResolverAliasShouldHaveCorrectConfiguration(): void
    {
        // 调用load方法
        $this->extension->load([], $this->container);

        // 获取别名
        $alias = $this->container->getAlias('Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface');

        // 验证别名指向正确的服务
        $this->assertEquals('Tourze\JsonRPCContainerBundle\Service\MethodResolver', (string) $alias);

        // 验证别名是公开的
        $this->assertTrue($alias->isPublic());
    }
}
