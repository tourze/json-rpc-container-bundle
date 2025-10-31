<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Fixtures;

use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;

class TestJsonRpcMethod implements JsonRpcMethodInterface
{
    public function __invoke(JsonRpcRequest $request): mixed
    {
        return ['test' => 'result'];
    }

    public function execute(): array
    {
        return ['test' => 'result'];
    }
}
