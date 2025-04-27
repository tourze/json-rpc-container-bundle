<?php

namespace Tourze\JsonRPCContainerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;

class JsonRpcProcedureCompilerPass implements CompilerPassInterface
{
    // And add an attribute with following key
    final public const JSONRPC_METHOD_TAG_METHOD_NAME_KEY = 'method';

    public function process(ContainerBuilder $container): void
    {
        $this->bindJsonRpcMethods($container);
    }

    private function bindJsonRpcMethods(ContainerBuilder $container): void
    {
        // $mappingAwareServiceDefinitionList = $this->findAndValidateMappingAwareDefinitionList($container);

        $jsonRpcMethodDefinitionList = self::findAndValidateJsonRpcMethodDefinition($container);
        $methodMappingList = [];
        foreach ($jsonRpcMethodDefinitionList as $jsonRpcMethodServiceId => $methodNameList) {
            foreach ($methodNameList as $methodName) {
                $methodMappingList[$methodName] = new Reference($jsonRpcMethodServiceId);
                $container->getDefinition($jsonRpcMethodServiceId)->setShared(false); // JsonRPC方法，目前有副作用，所以永远不应该是shared的
            }
        }

        // Service locator for method resolver
        // => first argument is an array of wanted service with keys as alias for internal use
        $container->getDefinition('json_rpc_http_server.service_locator.method_resolver')->setArgument(0, $methodMappingList);
    }

    /**
     * 读取所有有可能的Procedure
     */
    public static function findAndValidateJsonRpcMethodDefinition(ContainerBuilder $container): array
    {
        $definitionList = [];

        // 方法定义
        foreach ($container->findTaggedServiceIds(MethodExpose::JSONRPC_METHOD_TAG) as $serviceId => $tagAttributeList) {
            $procedureDef = $container->getDefinition($serviceId);
            static::validateJsonRpcMethodDefinition($serviceId, $procedureDef);
            foreach ($tagAttributeList as $tagAttributeKey => $tagAttributeData) {
                static::validateJsonRpcMethodTagAttributes($serviceId, $tagAttributeData);
                $methodName = $tagAttributeData[JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY];
                $definitionList[$serviceId][] = $methodName;
            }
        }

        return $definitionList;
    }

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
        if (!in_array(JsonRpcMethodInterface::class, class_implements($definition->getClass()))) {
            throw new LogicException(sprintf('Service "%s" is taggued as JSON-RPC method but does not implement %s', $serviceId, JsonRpcMethodInterface::class));
        }
    }
}
