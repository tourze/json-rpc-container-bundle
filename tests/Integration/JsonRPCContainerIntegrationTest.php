<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface;
use Tourze\JsonRPCContainerBundle\JsonRPCContainerBundle;
use Tourze\JsonRPCContainerBundle\Service\MethodResolver;

/**
 * JsonRPCContainer集成测试
 */
class JsonRPCContainerIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): TestKernel
    {
        $appendBundles = [
            FrameworkBundle::class => ['all' => true],
            JsonRPCContainerBundle::class => ['all' => true],
        ];
        
        $entityMappings = [];

        return new TestKernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? true,
            $appendBundles,
            $entityMappings
        );
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * 测试服务注册是否正确
     */
    public function testServiceRegistration_shouldRegisterServices(): void
    {
        $container = self::getContainer();

        // 测试服务定位器是否注册
        $this->assertTrue($container->has('json_rpc_http_server.service_locator.method_resolver'));

        // 测试方法解析器服务是否注册
        $this->assertTrue($container->has(MethodResolver::class));

        // 测试方法解析器接口是否注册并指向正确的实现
        $this->assertTrue($container->has(JsonRpcMethodResolverInterface::class));
        $resolver = $container->get(JsonRpcMethodResolverInterface::class);
        $this->assertInstanceOf(MethodResolver::class, $resolver);
    }

    /**
     * 测试方法解析功能
     */
    public function testMethodResolution_shouldResolveMethod(): void
    {
        $methodName = 'test.method';

        /** @var JsonRpcMethodResolverInterface $resolver */
        $resolver = self::getContainer()->get(JsonRpcMethodResolverInterface::class);

        // 解析已注册的方法
        $method = $resolver->resolve($methodName);

        // 验证方法解析结果
        $this->assertNotNull($method);
        $this->assertInstanceOf(JsonRpcMethodInterface::class, $method);
    }

    /**
     * 测试解析不存在的方法
     */
    public function testMethodResolution_withNonExistingMethod_shouldReturnNull(): void
    {
        $methodName = 'nonexistent.method';

        /** @var JsonRpcMethodResolverInterface $resolver */
        $resolver = self::getContainer()->get(JsonRpcMethodResolverInterface::class);

        // 解析未注册的方法
        $method = $resolver->resolve($methodName);

        // 验证方法解析结果
        $this->assertNull($method);
    }

    /**
     * 测试获取所有方法名
     */
    public function testGetAllMethodNames_shouldReturnRegisteredMethods(): void
    {
        /** @var JsonRpcMethodResolverInterface $resolver */
        $resolver = self::getContainer()->get(JsonRpcMethodResolverInterface::class);

        // 获取所有方法名
        $methodNames = $resolver->getAllMethodNames();

        // 验证结果
        $this->assertIsArray($methodNames);
        $this->assertContains('test.method', $methodNames);
    }
}
