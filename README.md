# tinyApi

极其精简的 Api 脚手架。

### features

- 使用 composer
- DB：[catfan/Medoo](https://github.com/catfan/Medoo)
- Redis：[nrk/predis](https://github.com/nrk/predis)
- Log：[Seldaek/monolog](https://github.com/Seldaek/monolog)
- 不支持 RESTful
- 不支持自定义路由
- 默认 POST，参数强制使用 Json
- 默认开启 AES
- 默认开启 VERIFY_TIMESTAMP/VERIFY_HASH

### global functions

- `_config(string $file, string $key = null)`：获取配置
- `_success(array $data): void`：exit 成功 json
- `_error(int $code, string $msg): void`：exit 失败 json
- `_medoo(): \Medoo\Medoo`：获取单例 Medoo
- `_predis(): \Predis\Client`：获取单例 Predis
- `_monolog(string $subDir = null): \Monolog\Logger`：获取单例 Monolog