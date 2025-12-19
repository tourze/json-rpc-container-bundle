<?php

namespace Tourze\JsonRPCContainerBundle\Tests\Fixtures;

use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Contracts\RpcResultInterface;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Result\ArrayResult;

final class TestJsonRpcMethodWithoutParamDoc implements JsonRpcMethodInterface
{
    public function __invoke(JsonRpcRequest $request): RpcResultInterface
    {
        return new ArrayResult(['test' => 'result']);
    }

    public function execute(TestJsonRpcMethodParam|RpcParamInterface $param): RpcResultInterface
    {
        return new ArrayResult(['test' => 'result']);
    }
}

