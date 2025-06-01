<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tourze\JsonRPCContainerBundle\Service\MethodResolver;

/**
 * MethodResolver异常和错误场景测试
 */
class MethodResolverExceptionTest extends TestCase
{
    /**
     * 测试当服务定位器抛出容器异常时的处理
     */
    public function testResolve_whenLocatorThrowsContainerException_shouldPropagateException(): void
    {
        $methodName = 'test.method';

        // 创建一个会抛出容器异常的模拟定位器
        $locator = $this->createMock(ContainerInterface::class);
        $locator->expects($this->once())
            ->method('has')
            ->with($methodName)
            ->willReturn(true);

        $locator->expects($this->once())
            ->method('get')
            ->with($methodName)
            ->willThrowException(new class extends \Exception implements ContainerExceptionInterface {});

        $resolver = new MethodResolver($locator);

        // 期望异常被传播
        $this->expectException(ContainerExceptionInterface::class);
        $resolver->resolve($methodName);
    }

    /**
     * 测试当服务定位器抛出NotFound异常时的处理
     */
    public function testResolve_whenLocatorThrowsNotFoundException_shouldPropagateException(): void
    {
        $methodName = 'test.method';

        // 创建一个会抛出NotFound异常的模拟定位器
        $locator = $this->createMock(ContainerInterface::class);
        $locator->expects($this->once())
            ->method('has')
            ->with($methodName)
            ->willReturn(true);

        $locator->expects($this->once())
            ->method('get')
            ->with($methodName)
            ->willThrowException(new class extends \Exception implements NotFoundExceptionInterface {});

        $resolver = new MethodResolver($locator);

        // 期望异常被传播
        $this->expectException(NotFoundExceptionInterface::class);
        $resolver->resolve($methodName);
    }

    /**
     * 测试当服务定位器的has方法抛出异常时的处理
     */
    public function testResolve_whenLocatorHasMethodThrowsException_shouldPropagateException(): void
    {
        $methodName = 'test.method';

        // 创建一个has方法会抛出异常的模拟定位器
        $locator = $this->createMock(ContainerInterface::class);
        $locator->expects($this->once())
            ->method('has')
            ->with($methodName)
            ->willThrowException(new \RuntimeException('Service locator error'));

        $resolver = new MethodResolver($locator);

        // 期望异常被传播
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Service locator error');
        $resolver->resolve($methodName);
    }

    /**
     * 测试当getProvidedServices方法不存在时的处理
     */
    public function testGetAllMethodNames_whenLocatorDoesNotHaveGetProvidedServices_shouldThrowError(): void
    {
        // 创建一个标准的ContainerInterface实现，不包含getProvidedServices方法
        $locator = new class implements ContainerInterface {
            public function get(string $id)
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }
        };

        $resolver = new MethodResolver($locator);

        // 期望抛出Error或BadMethodCallException
        $this->expectException(\Error::class);
        $resolver->getAllMethodNames();
    }

    /**
     * 测试当getProvidedServices返回非数组时的处理
     */
    public function testGetAllMethodNames_whenGetProvidedServicesReturnsNonArray_shouldHandleGracefully(): void
    {
        // 创建一个getProvidedServices返回非数组的容器
        $locator = new class implements ContainerInterface {
            public function get(string $id)
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }

            public function getProvidedServices()
            {
                return 'not an array';
            }
        };

        $resolver = new MethodResolver($locator);

        // 这应该会导致array_keys函数出错
        $this->expectException(\TypeError::class);
        $resolver->getAllMethodNames();
    }

    /**
     * 测试当getProvidedServices返回null时的处理
     */
    public function testGetAllMethodNames_whenGetProvidedServicesReturnsNull_shouldHandleGracefully(): void
    {
        // 创建一个getProvidedServices返回null的容器
        $locator = new class implements ContainerInterface {
            public function get(string $id)
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }

            public function getProvidedServices()
            {
                return null;
            }
        };

        $resolver = new MethodResolver($locator);

        // 这应该会导致array_keys函数出错
        $this->expectException(\TypeError::class);
        $resolver->getAllMethodNames();
    }

    /**
     * 测试环境变量值为null时的处理
     */
    public function testResolve_withNullEnvironmentVariable_shouldNotRemap(): void
    {
        $methodName = 'test.method';

        // 设置环境变量为null（在PHP中这实际上是字符串'null'或被unset）
        unset($_ENV["JSON_RPC_METHOD_REMAP_{$methodName}"]);

        // 创建模拟定位器
        $locator = $this->createMock(ContainerInterface::class);
        $locator->expects($this->once())
            ->method('has')
            ->with($methodName) // 应该使用原始方法名，不进行重映射
            ->willReturn(false);

        $locator->expects($this->never())
            ->method('get');

        $resolver = new MethodResolver($locator);

        // 调用方法
        $result = $resolver->resolve($methodName);

        // 验证结果
        $this->assertNull($result);
    }
} 