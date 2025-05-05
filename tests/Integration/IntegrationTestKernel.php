<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPCContainerBundle\JsonRPCContainerBundle;
use Tourze\JsonRPCContainerBundle\Tests\Fixtures\TestJsonRpcMethod;

class IntegrationTestKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new JsonRPCContainerBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // 基本框架配置
        $container->extension('framework', [
            'secret' => 'TEST_SECRET',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
        ]);

        // 注册测试JSON-RPC方法
        $container->services()
            ->set('test.jsonrpc.method', TestJsonRpcMethod::class)
            ->tag(MethodExpose::JSONRPC_METHOD_TAG, ['method' => 'test.method']);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }
}
