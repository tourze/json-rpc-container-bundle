<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\JsonRPCContainerBundle\DependencyInjection\JsonRPCContainerExtension;

/**
 * JsonRPCContainerExtension单元测试
 */
class JsonRPCContainerExtensionTest extends TestCase
{
    private JsonRPCContainerExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new JsonRPCContainerExtension();
        $this->container = new ContainerBuilder();
    }

    /**
     * 测试load方法是否正确加载服务配置
     */
    public function testLoad_shouldLoadServices(): void
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
}
