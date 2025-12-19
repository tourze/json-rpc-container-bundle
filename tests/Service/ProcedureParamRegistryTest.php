<?php

declare(strict_types=1);

namespace Tourze\JsonRPCContainerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPCContainerBundle\Service\ProcedureParamRegistry;

/**
 * @internal
 */
#[CoversClass(ProcedureParamRegistry::class)]
final class ProcedureParamRegistryTest extends TestCase
{
    public function testGetParamClassReturnsNullForUnregistered(): void
    {
        $registry = new ProcedureParamRegistry();
        $this->assertNull($registry->getParamClass('NonExistentClass'));
    }

    public function testGetParamClassReturnsRegisteredClass(): void
    {
        $mapping = [
            'App\Procedure\FooProcedure' => 'App\Param\FooParam',
        ];
        $registry = new ProcedureParamRegistry($mapping);

        $this->assertSame('App\Param\FooParam', $registry->getParamClass('App\Procedure\FooProcedure'));
    }

    public function testHasReturnsFalseForUnregistered(): void
    {
        $registry = new ProcedureParamRegistry();
        $this->assertFalse($registry->has('NonExistentClass'));
    }

    public function testHasReturnsTrueForRegistered(): void
    {
        $mapping = [
            'App\Procedure\FooProcedure' => 'App\Param\FooParam',
        ];
        $registry = new ProcedureParamRegistry($mapping);

        $this->assertTrue($registry->has('App\Procedure\FooProcedure'));
    }

    public function testGetAllReturnsEmptyArrayByDefault(): void
    {
        $registry = new ProcedureParamRegistry();
        $this->assertSame([], $registry->getAll());
    }

    public function testGetAllReturnsAllMappings(): void
    {
        $mapping = [
            'App\Procedure\FooProcedure' => 'App\Param\FooParam',
            'App\Procedure\BarProcedure' => 'App\Param\BarParam',
        ];
        $registry = new ProcedureParamRegistry($mapping);

        $this->assertSame($mapping, $registry->getAll());
    }
}
