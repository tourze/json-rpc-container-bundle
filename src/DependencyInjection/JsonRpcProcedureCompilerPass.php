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
        $methodMappingList = [];

        // Get services tagged with JSONRPC_METHOD_TAG
        $taggedServices = $container->findTaggedServiceIds(MethodExpose::JSONRPC_METHOD_TAG);

        foreach ($taggedServices as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);

            // Validate the service implements JsonRpcMethodInterface
            self::validateJsonRpcMethodDefinition($serviceId, $definition);

            // Set service as non-shared
            $definition->setShared(false);

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
