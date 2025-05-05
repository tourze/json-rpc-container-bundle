<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\JsonRPCContainerBundle\DependencyInjection\JsonRpcProcedureCompilerPass;
use Tourze\JsonRPCContainerBundle\JsonRPCContainerBundle;

/**
 * JsonRPCContainerBundle单元测试
 */
class JsonRPCContainerBundleTest extends TestCase
{
    /**
     * 测试build方法是否正确添加了编译器Pass
     */
    public function testBuild_shouldAddCompilerPass(): void
    {
        // 创建模拟的ContainerBuilder
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        // 期望添加编译器Pass
        $containerBuilder->expects($this->once())
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(JsonRpcProcedureCompilerPass::class));

        // 创建Bundle并调用build方法
        $bundle = new JsonRPCContainerBundle();
        $bundle->build($containerBuilder);
    }
}
