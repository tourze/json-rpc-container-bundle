<?php

namespace Tourze\JsonRPCContainerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\JsonRPCContainerBundle\DependencyInjection\JsonRpcProcedureCompilerPass;

class JsonRPCContainerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new JsonRpcProcedureCompilerPass());
    }
}
