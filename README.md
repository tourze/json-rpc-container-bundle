# JsonRPCContainerBundle

这是一个Symfony Bundle，用于注册和管理JSON-RPC方法。该Bundle为`tourze/json-rpc-core`提供了容器集成，使JSON-RPC方法可以通过Symfony容器进行注册和解析。

## 功能特性

- 使用Symfony容器来管理JSON-RPC方法
- 支持通过标签自动注册JSON-RPC方法
- 提供方法解析器实现，自动解析JSON-RPC方法
- 支持通过环境变量进行方法重映射

## 安装

通过Composer安装:

```bash
composer require tourze/json-rpc-container-bundle
```

## 配置

在你的Symfony应用程序中注册Bundle：

```php
// config/bundles.php
return [
    // ...
    Tourze\JsonRPCContainerBundle\JsonRPCContainerBundle::class => ['all' => true],
];
```

## 使用方法

### 创建JSON-RPC方法

首先，创建一个实现`JsonRpcMethodInterface`的类：

```php
<?php

namespace App\JsonRpc;

use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;

class ExampleMethod implements JsonRpcMethodInterface
{
    public function __invoke(JsonRpcRequest $request): mixed
    {
        // 处理JSON-RPC请求
        return ['success' => true, 'data' => 'example_result'];
    }
    
    public function execute(): array
    {
        // 兼容接口实现
        return ['success' => true, 'data' => 'example_result'];
    }
}
```

### 注册JSON-RPC方法

使用`json_rpc_http_server.jsonrpc_method`标签注册方法：

#### 通过服务配置注册

```yaml
# config/services.yaml
services:
    app.jsonrpc.example_method:
        class: App\JsonRpc\ExampleMethod
        tags:
            - { name: 'json_rpc_http_server.jsonrpc_method', method: 'example.method' }
```

#### 通过属性注册

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

### 解析JSON-RPC方法

通过服务容器获取`JsonRpcMethodResolverInterface`实例：

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
        // 解析指定方法
        $method = $methodResolver->resolve('example.method');
        
        // 获取所有已注册的方法名
        $allMethods = $methodResolver->getAllMethodNames();
        
        // ...
    }
}
```

### 方法重映射

可以通过环境变量重映射方法名：

```
# .env
JSON_RPC_METHOD_REMAP_original.method=remapped.method
```

这将使对`original.method`的请求被重定向到`remapped.method`。

## 测试

运行单元测试：

```bash
./vendor/bin/phpunit packages/json-rpc-container-bundle/tests
```

## 许可证

MIT
