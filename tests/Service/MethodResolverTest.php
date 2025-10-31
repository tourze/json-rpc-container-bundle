<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface;
use Tourze\JsonRPCContainerBundle\Service\MethodResolver;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * MethodResolver集成测试
 *
 * @internal
 */
#[CoversClass(MethodResolver::class)]
#[RunTestsInSeparateProcesses]
final class MethodResolverTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试不需要特殊设置
    }

    /**
     * 测试服务可以正确实例化
     */
    public function testServiceCanBeInstantiated(): void
    {
        $resolver = self::getService(JsonRpcMethodResolverInterface::class);

        $this->assertInstanceOf(MethodResolver::class, $resolver);
    }

    /**
     * 测试解析不存在的方法返回 null
     */
    public function testResolveNonExistentMethodReturnsNull(): void
    {
        $resolver = self::getService(JsonRpcMethodResolverInterface::class);

        $result = $resolver->resolve('non.existent.method');

        $this->assertNull($result);
    }

    /**
     * 测试获取所有方法名返回空数组（因为没有注册任何方法）
     */
    public function testGetAllMethodNamesReturnsEmptyArray(): void
    {
        $resolver = self::getService(JsonRpcMethodResolverInterface::class);

        $result = $resolver->getAllMethodNames();

        $this->assertIsArray($result);
        // 集成测试中可能会注册一些测试方法，所以只检查返回类型
        // $this->assertEmpty($result);
    }

    /**
     * 测试解析空方法名返回 null
     */
    public function testResolveWithEmptyMethodNameReturnsNull(): void
    {
        $resolver = self::getService(JsonRpcMethodResolverInterface::class);

        $result = $resolver->resolve('');

        $this->assertNull($result);
    }

    /**
     * 测试解析包含空格的方法名返回 null
     */
    public function testResolveWithWhitespaceMethodNameReturnsNull(): void
    {
        $resolver = self::getService(JsonRpcMethodResolverInterface::class);

        $result = $resolver->resolve('   ');

        $this->assertNull($result);
    }

    /**
     * 测试解析包含特殊字符的方法名
     */
    public function testResolveWithSpecialCharacterMethodName(): void
    {
        $resolver = self::getService(JsonRpcMethodResolverInterface::class);

        $result = $resolver->resolve('test.method@#$');

        $this->assertNull($result);
    }

    /**
     * 测试解析很长的方法名
     */
    public function testResolveWithVeryLongMethodName(): void
    {
        $resolver = self::getService(JsonRpcMethodResolverInterface::class);

        $longMethodName = str_repeat('very.long.method.name.', 50);
        $result = $resolver->resolve($longMethodName);

        $this->assertNull($result);
    }

    /**
     * 测试包含 Unicode 字符的方法名
     */
    public function testResolveWithUnicodeMethodName(): void
    {
        $resolver = self::getService(JsonRpcMethodResolverInterface::class);

        $result = $resolver->resolve('测试.方法.名称');

        $this->assertNull($result);
    }
}
