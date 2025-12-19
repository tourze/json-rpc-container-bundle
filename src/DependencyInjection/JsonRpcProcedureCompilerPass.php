<?php

namespace Tourze\JsonRPCContainerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPCContainerBundle\Service\ProcedureParamRegistry;

final class JsonRpcProcedureCompilerPass implements CompilerPassInterface
{
    // And add an attribute with following key
    final public const JSONRPC_METHOD_TAG_METHOD_NAME_KEY = 'method';

    public function process(ContainerBuilder $container): void
    {
        $this->bindJsonRpcMethods($container);
    }

    private function bindJsonRpcMethods(ContainerBuilder $container): void
    {
        $methodMappingList = [];
        $procedureParamMapping = [];

        // Get services tagged with JSONRPC_METHOD_TAG
        $taggedServices = $container->findTaggedServiceIds(MethodExpose::JSONRPC_METHOD_TAG);

        foreach ($taggedServices as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);

            // Validate the service implements JsonRpcMethodInterface
            self::validateJsonRpcMethodDefinition($serviceId, $definition);

            // 不再强制将 Procedure 设为非共享（shared: false）。
            // Procedure 应保持无状态；如确有需要，请在服务定义里显式配置 shared: false。

            // Extract and validate Param class from execute method signature
            $className = $definition->getClass();
            if (null !== $className) {
                $paramClass = self::extractAndValidateParamClass($className);
                self::validateExecuteParamPhpDoc($className, $paramClass);
                $procedureParamMapping[$className] = $paramClass;
            }

            // Process each tag to get method names
            foreach ($tags as $tagAttribute) {
                self::validateJsonRpcMethodTagAttributes($serviceId, $tagAttribute);
                $methodName = $tagAttribute[self::JSONRPC_METHOD_TAG_METHOD_NAME_KEY];
                $methodMappingList[$methodName] = new Reference($serviceId);
            }
        }

        // Service locator for method resolver
        // => first argument is an array of wanted service with keys as alias for internal use
        $container->getDefinition('json_rpc_http_server.service_locator.method_resolver')->setArgument(0, $methodMappingList);

        // Inject Procedure -> Param mapping into registry
        if ($container->hasDefinition(ProcedureParamRegistry::class)) {
            $container->getDefinition(ProcedureParamRegistry::class)->setArgument(0, $procedureParamMapping);
        }
    }

    /**
     * 从 execute 方法签名中提取并验证 Param 类
     *
     * @param class-string $className
     *
     * @return class-string<RpcParamInterface>
     *
     * @throws LogicException 如果 execute 方法签名不符合规范
     */
    private static function extractAndValidateParamClass(string $className): string
    {
        $reflection = new \ReflectionClass($className);

        if ($reflection->isAbstract()) {
            throw new LogicException(sprintf('Abstract class "%s" should not be tagged as JSON-RPC method', $className));
        }

        $type = self::getExecuteParamType($reflection, $className);
        $paramClass = self::findConcreteParamClass($type);

        if (null !== $paramClass) {
            return $paramClass;
        }

        throw new LogicException(sprintf('Procedure "%s" execute() parameter type "%s" is invalid. Expected: ConcreteParam|RpcParamInterface', $className, self::formatType($type)));
    }

    /**
     * 强制要求 execute 方法通过 @phpstan-param 标注具体 Param 类型。
     *
     * 背景：由于 Param 类实现了 RpcParamInterface，execute 的联合类型（ConcreteParam|RpcParamInterface）
     * 在静态分析时容易被收敛为 RpcParamInterface，导致访问 $param->xxx 被误判为不存在。
     * 使用 @phpstan-param 可以让 PHPStan 识别具体类型，同时 IDE 直接用方法签名的联合类型不会报错。
     *
     * @param class-string $className
     * @param class-string<RpcParamInterface> $paramClass
     *
     * @throws LogicException|\ReflectionException 如果缺少或标注错误
     */
    private static function validateExecuteParamPhpDoc(string $className, string $paramClass): void
    {
        $reflection = new \ReflectionClass($className);
        $method = $reflection->getMethod('execute');
        $params = $method->getParameters();
        if (0 === count($params)) {
            throw new LogicException(sprintf('Procedure "%s" execute() must have a parameter implementing RpcParamInterface', $className));
        }

        $paramName = $params[0]->getName();
        $docComment = $method->getDocComment();
        $expectedShort = self::getShortClassName($paramClass);

        if (false === $docComment) {
            throw new LogicException(sprintf('Procedure "%s" execute() must declare "@phpstan-param %s $%s"', $className, $expectedShort, $paramName));
        }

        $pattern = '/@phpstan-param\s+([^\s]+)\s+\$' . preg_quote($paramName, '/') . '\b/';
        if (1 !== preg_match($pattern, $docComment, $matches)) {
            throw new LogicException(sprintf('Procedure "%s" execute() must declare "@phpstan-param %s $%s"', $className, $expectedShort, $paramName));
        }

        $docType = $matches[1];

        if (!self::matchesParamClass($docType, $paramClass)) {
            throw new LogicException(sprintf('Procedure "%s" execute() @phpstan-param type must be "%s", "%s" given', $className, $expectedShort, $docType));
        }
    }

    /**
     * 检查 docType 是否匹配预期的参数类。
     *
     * @param class-string<RpcParamInterface> $paramClass
     */
    private static function matchesParamClass(string $docType, string $paramClass): bool
    {
        $expectedFqcn = ltrim($paramClass, '\\');
        $expectedShort = self::getShortClassName($paramClass);
        $normalizedDocType = ltrim($docType, '\\');

        return 0 === strcasecmp($normalizedDocType, $expectedFqcn)
            || 0 === strcasecmp($normalizedDocType, $expectedShort);
    }

    /**
     * @param class-string $className
     */
    private static function getShortClassName(string $className): string
    {
        $pos = strrpos($className, '\\');
        if (false === $pos) {
            return $className;
        }

        return substr($className, $pos + 1);
    }

    /**
     * @return class-string<RpcParamInterface>|null
     */
    private static function findConcreteParamClass(\ReflectionType $type): ?string
    {
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($types as $t) {
            if (!$t instanceof \ReflectionNamedType) {
                continue;
            }
            $name = $t->getName();
            if (RpcParamInterface::class !== $name && is_a($name, RpcParamInterface::class, true)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param class-string $className
     */
    private static function getExecuteParamType(\ReflectionClass $reflection, string $className): \ReflectionType
    {
        $params = $reflection->getMethod('execute')->getParameters();

        if (0 === count($params)) {
            throw new LogicException(sprintf('Procedure "%s" execute() must have a parameter implementing RpcParamInterface', $className));
        }

        $type = $params[0]->getType();
        if (null === $type) {
            throw new LogicException(sprintf('Procedure "%s" execute() parameter must have a type declaration', $className));
        }

        return $type;
    }

    private static function formatType(\ReflectionType $type): string
    {
        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(
                fn ($t) => $t instanceof \ReflectionNamedType ? $t->getName() : '?',
                $type->getTypes()
            ));
        }

        return $type instanceof \ReflectionNamedType ? $type->getName() : '?';
    }

    /**
     * @param array<string, mixed> $tagAttributeData
     */
    private static function validateJsonRpcMethodTagAttributes(string $serviceId, array $tagAttributeData): void
    {
        if (!isset($tagAttributeData[JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY])) {
            throw new LogicException(sprintf('Service "%s" is taggued as JSON-RPC method but does not have method name defined under "%s" tag attribute key', $serviceId, JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY));
        }
    }

    /**
     * @throws \LogicException In case definition is not valid
     */
    private static function validateJsonRpcMethodDefinition(string $serviceId, Definition $definition): void
    {
        $className = $definition->getClass();
        if (null === $className) {
            throw new LogicException(sprintf('Service "%s" has no class defined', $serviceId));
        }

        $interfaces = class_implements($className);
        if (false === $interfaces || !in_array(JsonRpcMethodInterface::class, $interfaces, true)) {
            throw new LogicException(sprintf('Service "%s" is taggued as JSON-RPC method but does not implement %s', $serviceId, JsonRpcMethodInterface::class));
        }
    }
}
