## ThinkPHP-PanGu - 盘古ThinkPHP开源索引!
面向 ThinkPHP 的开源工程索引：组件、框架、服务与发布工具，按能力组织，持续整理。
仓库地址：**<https://github.com/indiboy-create/ThinkPHP-PanGu/>**
在线站点：**<https://indiboy-create.github.io/ThinkPHP-PanGu/>**

# think-jwt

基于 [firebase/php-jwt](https://github.com/firebase/php-jwt) 的 **ThinkPHP 8** JWT 认证扩展，开箱即用，支持令牌签发、验证、刷新与注销（黑名单）。

## 特性

- 仅支持 **ThinkPHP 8**（不兼容 ThinkPHP 6）
- 基于 `firebase/php-jwt ^7.2`，默认 `HS256` 算法
- `Facade` 门面调用，一行代码即可签发 / 校验
- 令牌唯一标识（`jwt_ide`），配合缓存实现刷新与注销黑名单
- 完整错误码，便于前端统一处理

## 环境要求

| 依赖 | 版本 |
| --- | --- |
| PHP | `>= 8.2` |
| ThinkPHP | `^8.0` |
| firebase/php-jwt | `^7.2` |

## 安装

```sh
composer require youvision/think-jwt
```

安装后配置文件会自动发布到 `config/jwt.php`。

## 配置

```php
<?php

declare(strict_types=1);

return [
    // 加密密钥，必须设置，否则构造时抛出异常
    'secret'  => 'ai@youvision',
    // 刷新阈值（秒），用于判断令牌是否需要刷新，默认 10 小时
    'exp'     => (60 * 60) * 10,
    // 令牌有效期（秒），同时作为黑名单缓存时长，默认 7 天
    'refresh' => ((60 * 60) * 24) * 7,
];
```

> `secret` 为空时，`Jwt` 实例化即抛出 `未设置jwt秘钥`（错误码 `50002`）。

## 快速开始

```php
<?php

namespace app\index\controller;

use think\facade\Jwt;

class Auth
{
    /**
     * 签发令牌
     */
    public function create()
    {
        return Jwt::getToken(['user_id' => 12]);
    }

    /**
     * 校验令牌（从请求头读取）
     */
    public function check()
    {
        $token = Jwt::getRequestHeaders();

        $res = Jwt::Check($token);
        if ($res['code'] !== 1) {
            return json(['code' => 0, 'msg' => $res['msg']]);
        }

        return json(['code' => 1, 'data' => $res['data']]);
    }

    /**
     * 刷新令牌
     */
    public function refresh()
    {
        return Jwt::Refresh(Jwt::getRequestHeaders());
    }

    /**
     * 注销令牌
     */
    public function logout()
    {
        return Jwt::Logout(Jwt::getRequestHeaders());
    }
}
```

请求头格式：

```
Authorization: Bearer <token>
```

## API

| 方法 | 说明 | 返回 |
| --- | --- | --- |
| `getToken(array $data = [])` | 签发令牌，`$data` 为自定义载荷 | `string` JWT |
| `Check(string $token = '')` | 校验令牌有效性 | `array` `{code, msg, data}` |
| `Refresh(string $token = '')` | 刷新令牌，旧令牌加入黑名单 | `array` `{code, msg, data}` |
| `Logout(string $token = '')` | 注销令牌，加入黑名单 | `array` `{code, msg, data}` |
| `Parse(string $token = '', int $type = 0)` | 解析令牌，`$type=0` 返回 `data`，`$type=1` 返回完整载荷 | `array` |
| `getRequestHeaders()` | 从 `$_SERVER['HTTP_AUTHORIZATION']` 提取令牌 | `string` |
| `getRequestToken(Request $request)` | 从请求对象提取令牌（含备用头） | `string` |

### 返回结构

`Check`、`Refresh`、`Logout` 统一返回：

```php
[
    'code' => 1,      // 1 成功，0 失败
    'msg'  => '',     // 提示信息
    'data' => [],     // 业务数据或新令牌
]
```

### Parse 返回

```php
// $type = 0（默认）返回自定义业务数据
Jwt::Parse($token);

// $type = 1 返回完整载荷
// ['nbf' => ..., 'exp' => ..., 'jwt_ide' => ..., 'data' => [...]]
Jwt::Parse($token, 1);
```

## 错误码

| 常量 | 值 | 说明 |
| --- | --- | --- |
| `ENCRYPT_EORROR` | `50001` | JWT 加密运算异常 |
| `JWT_SECRET_MISS` | `50002` | 未设置 JWT 密钥 |
| `INVALID_TOKEN` | `40001` | 令牌格式非法 / 签名无效 / 尚未生效 |
| `TOKEN_EXPIRE` | `20001` | 令牌需要刷新 |
| `TOKEN_EXPIRE_LONG` | `20002` | 令牌已过期 |
| `TOKEN_LOGOUT` | `20003` | 令牌已被注销 |
| `TOKEN_DOMAIN` | `20004` | 域名失效 |

错误码以异常码形式抛出，可通过 `$e->getCode()` 获取：

```php
try {
    Jwt::Parse($token);
} catch (\Exception $e) {
    return json(['code' => $e->getCode(), 'msg' => $e->getMessage()]);
}
```

## 黑名单机制

刷新与注销时，令牌的唯一标识 `jwt_ide` 会写入缓存（键名 `jwt_ide_{id}`，时长与 `refresh` 一致）。后续 `Parse` / `Check` 时命中即判定令牌已失效。

> 请确保应用已配置缓存驱动，并保证多节点部署时缓存共享。

## 示例

完整示例见 [`examples/`](examples) 目录：

| 文件 | 说明 |
| --- | --- |
| `examples/config/jwt.php` | 配置文件，复制到应用 `config/jwt.php` |
| `examples/controller/Auth.php` | 签发 / 校验 / 刷新 / 注销控制器 |
| `examples/middleware/JwtAuth.php` | 令牌鉴权中间件 |

路由注册（`route/app.php`）：

```php
use think\facade\Route;

Route::post('auth/create', 'Auth/create');
Route::get('auth/check', 'Auth/check');
Route::post('auth/refresh', 'Auth/refresh');
Route::post('auth/logout', 'Auth/logout');

// 需要鉴权的接口分组
Route::group('api', function () {
    // ...
})->middleware(\app\middleware\JwtAuth::class);
```

## License

Apache-2.0