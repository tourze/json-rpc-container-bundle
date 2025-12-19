<?php

declare(strict_types=1);

namespace Tourze\JsonRPCContainerBundle\Service;

use Tourze\JsonRPC\Core\Contracts\ProcedureParamRegistryInterface;

/**
 * Procedure 参数类注册表
 *
 * 存储由 CompilerPass 在编译时收集的 Procedure -> Param 映射
 */
final readonly class ProcedureParamRegistry implements ProcedureParamRegistryInterface
{
    /**
     * @param array<class-string, class-string> $mapping Procedure 类 -> Param 类的映射
     */
    public function __construct(
        private array $mapping = [],
    ) {
    }

    public function getParamClass(string $procedureClass): ?string
    {
        return $this->mapping[$procedureClass] ?? null;
    }

    public function has(string $procedureClass): bool
    {
        return isset($this->mapping[$procedureClass]);
    }

    public function getAll(): array
    {
        return $this->mapping;
    }
}
