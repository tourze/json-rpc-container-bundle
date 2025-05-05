<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPCContainerBundle\Service\MethodResolver;
use Tourze\JsonRPCContainerBundle\Tests\Fixtures\TestJsonRpcMethod;

/**
 * MethodResolver单元测试
 */
class MethodResolverTest extends TestCase
{
    private ContainerInterface $locator;
    private MethodResolver $resolver;

    protected function setUp(): void
    {
        $this->locator = $this->createMock(ContainerInterface::class);
        $this->resolver = new MethodResolver($this->locator);
    }

    /**
     * 测试正常方法解析
     */
    public function testResolve_withExistingMethod_shouldReturnMethod(): void
    {
        $methodName = 'test.method';
        $method = new TestJsonRpcMethod();

        // 模拟服务定位器
        $this->locator->expects($this->once())
            ->method('has')
            ->with($methodName)
            ->willReturn(true);

        $this->locator->expects($this->once())
            ->method('get')
            ->with($methodName)
            ->willReturn($method);

        // 调用方法
        $result = $this->resolver->resolve($methodName);

        // 验证结果
        $this->assertInstanceOf(JsonRpcMethodInterface::class, $result);
        $this->assertSame($method, $result);
    }

    /**
     * 测试解析不存在的方法
     */
    public function testResolve_withNonExistingMethod_shouldReturnNull(): void
    {
        $methodName = 'test.method.nonexistent';

        // 模拟服务定位器
        $this->locator->expects($this->once())
            ->method('has')
            ->with($methodName)
            ->willReturn(false);

        $this->locator->expects($this->never())
            ->method('get');

        // 调用方法
        $result = $this->resolver->resolve($methodName);

        // 验证结果
        $this->assertNull($result);
    }

    /**
     * 测试使用环境变量重映射方法名
     */
    public function testResolve_withMethodRemapping_shouldUseRemappedMethod(): void
    {
        $originalMethodName = 'original.method';
        $remappedMethodName = 'remapped.method';
        $method = new TestJsonRpcMethod();

        // 设置环境变量
        $_ENV["JSON_RPC_METHOD_REMAP_{$originalMethodName}"] = $remappedMethodName;

        // 模拟服务定位器
        $this->locator->expects($this->once())
            ->method('has')
            ->with($remappedMethodName)
            ->willReturn(true);

        $this->locator->expects($this->once())
            ->method('get')
            ->with($remappedMethodName)
            ->willReturn($method);

        // 调用方法
        $result = $this->resolver->resolve($originalMethodName);

        // 验证结果
        $this->assertInstanceOf(JsonRpcMethodInterface::class, $result);
        $this->assertSame($method, $result);

        // 清理环境变量
        unset($_ENV["JSON_RPC_METHOD_REMAP_{$originalMethodName}"]);
    }

    /**
     * 测试获取所有方法名
     */
    public function testGetAllMethodNames_shouldReturnAllMethodNames(): void
    {
        $methodNames = ['method1', 'method2', 'method3'];
        $providedServices = [];

        // 创建简单的服务映射
        foreach ($methodNames as $methodName) {
            $providedServices[$methodName] = 'some_service_id';
        }

        // 创建一个使用数组实现的服务容器，而不是模拟的ContainerInterface
        $locator = new class($providedServices) implements ContainerInterface {
            private array $services;

            public function __construct(array $services)
            {
                $this->services = $services;
            }

            public function get(string $id)
            {
                return $this->services[$id] ?? null;
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }

            public function getProvidedServices(): array
            {
                return $this->services;
            }
        };

        $resolver = new MethodResolver($locator);

        // 调用方法
        $result = $resolver->getAllMethodNames();

        // 验证结果
        $this->assertIsArray($result);
        $this->assertCount(count($methodNames), $result);
        $this->assertEquals($methodNames, $result);
    }
}
