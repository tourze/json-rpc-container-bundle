<?php

namespace Tourze\JsonRPCContainerBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface;

/**
 * Class MethodResolver
 */
#[WithMonologChannel(channel: 'json_rpc_container')]
readonly class MethodResolver implements JsonRpcMethodResolverInterface
{
    /**
     * @param ServiceProviderInterface<JsonRpcMethodInterface> $locator
     */
    public function __construct(
        #[Autowire(service: 'json_rpc_http_server.service_locator.method_resolver')] private ServiceProviderInterface $locator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function resolve(string $methodName): ?JsonRpcMethodInterface
    {
        // 兼容特殊情况
        if (isset($_ENV["JSON_RPC_METHOD_REMAP_{$methodName}"])) {
            $orgMethodName = $methodName;
            $methodName = $_ENV["JSON_RPC_METHOD_REMAP_{$methodName}"];
            $this->logger->debug('Method remapped', [
                'orgMethodName' => $orgMethodName,
                'newMethodName' => $methodName,
            ]);
        }

        return $this->locator->has($methodName)
            ? $this->locator->get($methodName)
            : null;
    }

    public function getAllMethodNames(): array
    {
        // 获取所有已定义的条目名称
        return array_keys($this->locator->getProvidedServices());
    }
}
