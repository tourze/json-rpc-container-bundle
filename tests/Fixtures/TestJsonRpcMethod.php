<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Fixtures;

use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;

/**
 * 用于测试的JSON-RPC方法实现
 */
class TestJsonRpcMethod implements JsonRpcMethodInterface
{
    public function __invoke(JsonRpcRequest $request): mixed
    {
        // 简单实现，返回固定结果
        return ['success' => true, 'data' => 'test_result'];
    }

    public function execute(): array
    {
        // 兼容接口实现
        return ['success' => true, 'data' => 'test_result'];
    }
}
