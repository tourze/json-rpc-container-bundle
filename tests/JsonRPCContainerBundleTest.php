<?php

declare(strict_types=1);

namespace Tourze\JsonRPCContainerBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPCContainerBundle\JsonRPCContainerBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(JsonRPCContainerBundle::class)]
#[RunTestsInSeparateProcesses]
final class JsonRPCContainerBundleTest extends AbstractBundleTestCase
{
}
