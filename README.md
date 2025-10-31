# JsonRPCContainerBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/json-rpc-container-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-container-bundle)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-container-bundle)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg?style=flat-square)
![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg?style=flat-square)

A Symfony Bundle for registering and managing JSON-RPC methods. This bundle provides container integration for `tourze/json-rpc-core`, enabling JSON-RPC methods to be registered and resolved through the Symfony container.

## Features

- Manage JSON-RPC methods using Symfony container
- Automatic registration of JSON-RPC methods through tags
- Method resolver implementation for automatic JSON-RPC method resolution
- Support for method remapping via environment variables

## Installation

Install via Composer:

```bash
composer require tourze/json-rpc-container-bundle
```

## Requirements

- PHP >= 8.1
- Symfony >= 7.3
- tourze/json-rpc-core

## Configuration

Register the bundle in your Symfony application:

```php
// config/bundles.php
return [
    // ...
    Tourze\JsonRPCContainerBundle\JsonRPCContainerBundle::class => ['all' => true],
];
```

## Usage

### Creating JSON-RPC Methods

First, create a class that implements `JsonRpcMethodInterface`:

```php
<?php

namespace App\JsonRpc;

use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;

class ExampleMethod implements JsonRpcMethodInterface
{
    public function __invoke(JsonRpcRequest $request): mixed
    {
        // Handle JSON-RPC request
        return ['success' => true, 'data' => 'example_result'];
    }
    
    public function execute(): array
    {
        // Interface compatibility implementation
        return ['success' => true, 'data' => 'example_result'];
    }
}
```

### Registering JSON-RPC Methods

Register methods using the `json_rpc_http_server.jsonrpc_method` tag:

#### Via Service Configuration

```yaml
# config/services.yaml
services:
    app.jsonrpc.example_method:
        class: App\JsonRpc\ExampleMethod
        tags:
            - { name: 'json_rpc_http_server.jsonrpc_method', method: 'example.method' }
```

#### Via Attributes

```php
<?php

namespace App\JsonRpc;

use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;

#[MethodExpose('example.method')]
class ExampleMethod implements JsonRpcMethodInterface
{
    // ...
}
```

### Resolving JSON-RPC Methods

Get an instance of `JsonRpcMethodResolverInterface` through the service container:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodResolverInterface;

class ApiController extends AbstractController
{
    public function handleRequest(JsonRpcMethodResolverInterface $methodResolver): Response
    {
        // Resolve specific method
        $method = $methodResolver->resolve('example.method');
        
        // Get all registered method names
        $allMethods = $methodResolver->getAllMethodNames();
        
        // ...
    }
}
```

### Method Remapping

You can remap method names through environment variables:

```dotenv
# .env
JSON_RPC_METHOD_REMAP_original.method=remapped.method
```

This will redirect requests for `original.method` to `remapped.method`.

## Testing

Run unit tests:

```bash
./vendor/bin/phpunit packages/json-rpc-container-bundle/tests
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
