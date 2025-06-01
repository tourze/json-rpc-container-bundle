<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tourze\JsonRPCContainerBundle\Service\MethodResolver;
use Tourze\JsonRPCContainerBundle\Tests\Fixtures\TestJsonRpcMethod;

/**
 * MethodResolver边界情况和异常场景测试
 */
class MethodResolverEdgeCaseTest extends TestCase
{
    private ContainerInterface $locator;
    private MethodResolver $resolver;

    protected function setUp(): void
    {
        $this->locator = $this->createMock(ContainerInterface::class);
        $this->resolver = new MethodResolver($this->locator);
    }

    /**
     * 测试解析空方法名
     */
    public function testResolve_withEmptyMethodName_shouldReturnNull(): void
    {
        $methodName = '';

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
     * 测试解析只包含空白字符的方法名
     */
    public function testResolve_withWhitespaceMethodName_shouldReturnNull(): void
    {
        $methodName = '   ';

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
     * 测试解析包含特殊字符的方法名
     */
    public function testResolve_withSpecialCharacterMethodName_shouldHandleCorrectly(): void
    {
        $methodName = 'test@method#with$special%chars';
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
        $this->assertSame($method, $result);
    }

    /**
     * 测试解析非常长的方法名
     */
    public function testResolve_withVeryLongMethodName_shouldHandleCorrectly(): void
    {
        $methodName = str_repeat('a', 1000) . '.method';
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
        $this->assertSame($method, $result);
    }

    /**
     * 测试环境变量重映射到空字符串
     */
    public function testResolve_withRemappingToEmptyString_shouldReturnNull(): void
    {
        $originalMethodName = 'original.method';
        $remappedMethodName = '';

        // 设置环境变量
        $_ENV["JSON_RPC_METHOD_REMAP_{$originalMethodName}"] = $remappedMethodName;

        // 模拟服务定位器
        $this->locator->expects($this->once())
            ->method('has')
            ->with($remappedMethodName)
            ->willReturn(false);

        $this->locator->expects($this->never())
            ->method('get');

        // 调用方法
        $result = $this->resolver->resolve($originalMethodName);

        // 验证结果
        $this->assertNull($result);

        // 清理环境变量
        unset($_ENV["JSON_RPC_METHOD_REMAP_{$originalMethodName}"]);
    }

    /**
     * 测试环境变量重映射到不存在的方法
     */
    public function testResolve_withRemappingToNonExistentMethod_shouldReturnNull(): void
    {
        $originalMethodName = 'original.method';
        $remappedMethodName = 'nonexistent.method';

        // 设置环境变量
        $_ENV["JSON_RPC_METHOD_REMAP_{$originalMethodName}"] = $remappedMethodName;

        // 模拟服务定位器
        $this->locator->expects($this->once())
            ->method('has')
            ->with($remappedMethodName)
            ->willReturn(false);

        $this->locator->expects($this->never())
            ->method('get');

        // 调用方法
        $result = $this->resolver->resolve($originalMethodName);

        // 验证结果
        $this->assertNull($result);

        // 清理环境变量
        unset($_ENV["JSON_RPC_METHOD_REMAP_{$originalMethodName}"]);
    }

    /**
     * 测试链式环境变量重映射（不应该发生，但测试边界情况）
     */
    public function testResolve_withChainedRemapping_shouldOnlyRemapOnce(): void
    {
        $originalMethodName = 'original.method';
        $firstRemappedName = 'first.remapped';
        $secondRemappedName = 'second.remapped';
        $method = new TestJsonRpcMethod();

        // 设置环境变量
        $_ENV["JSON_RPC_METHOD_REMAP_{$originalMethodName}"] = $firstRemappedName;
        $_ENV["JSON_RPC_METHOD_REMAP_{$firstRemappedName}"] = $secondRemappedName;

        // 模拟服务定位器 - 应该只查找第一次重映射的结果
        $this->locator->expects($this->once())
            ->method('has')
            ->with($firstRemappedName)
            ->willReturn(true);

        $this->locator->expects($this->once())
            ->method('get')
            ->with($firstRemappedName)
            ->willReturn($method);

        // 调用方法
        $result = $this->resolver->resolve($originalMethodName);

        // 验证结果
        $this->assertSame($method, $result);

        // 清理环境变量
        unset($_ENV["JSON_RPC_METHOD_REMAP_{$originalMethodName}"]);
        unset($_ENV["JSON_RPC_METHOD_REMAP_{$firstRemappedName}"]);
    }

    /**
     * 测试空的服务定位器
     */
    public function testGetAllMethodNames_withEmptyLocator_shouldReturnEmptyArray(): void
    {
        // 创建一个空的服务容器
        $emptyLocator = new class implements ContainerInterface {
            public function get(string $id)
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }

            public function getProvidedServices(): array
            {
                return [];
            }
        };

        $resolver = new MethodResolver($emptyLocator);

        // 调用方法
        $result = $resolver->getAllMethodNames();

        // 验证结果
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试解析包含unicode字符的方法名
     */
    public function testResolve_withUnicodeMethodName_shouldHandleCorrectly(): void
    {
        $methodName = 'test.方法.测试';
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
        $this->assertSame($method, $result);
    }
} 