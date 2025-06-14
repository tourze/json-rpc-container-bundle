<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Integration;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\JsonRPCContainerBundle\Tests\Fixtures\TestJsonRpcMethod;

/**
 * 测试专用内核
 */
class TestKernel extends IntegrationTestKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        
        $loader->load(function (ContainerBuilder $container) {
            // 注册测试用的 JSON-RPC 方法
            $container->register(TestJsonRpcMethod::class)
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->addTag('json_rpc_http_server.jsonrpc_method', ['method' => 'test.method']);
        });
    }
}