<?php

namespace Tourze\JsonRPCContainerBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class JsonRPCContainerExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
