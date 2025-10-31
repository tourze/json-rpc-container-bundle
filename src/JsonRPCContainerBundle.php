<?php

namespace Tourze\JsonRPCContainerBundle;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPCContainerBundle\DependencyInjection\JsonRpcProcedureCompilerPass;

class JsonRPCContainerBundle extends Bundle
{
    /**
     * @return array<string, mixed>
     */
    public static function getBundleDependencies(): array
    {
        return ['all' => true];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new JsonRpcProcedureCompilerPass());

        // 注册所有接口
        $container->registerAttributeForAutoconfiguration(MethodExpose::class, static function (ChildDefinition $definition, MethodExpose $expose): void {
            $definition->addTag(MethodExpose::JSONRPC_METHOD_TAG, [
                JsonRpcProcedureCompilerPass::JSONRPC_METHOD_TAG_METHOD_NAME_KEY => $expose->method,
            ]);
        });
    }
}
