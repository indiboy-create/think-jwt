<?php

declare(strict_types=1);

namespace app\middleware;

use think\facade\Jwt;
use think\Request;
use think\Response;

/**
 * 令牌鉴权中间件示例
 *
 * 注册方式（app/middleware.php 或路由分组）：
 *
 *   \app\middleware\JwtAuth::class
 */
class JwtAuth
{
    /**
     * @param Request  $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $token = (string) $request->header('authorization', '');
        $token = trim(str_ireplace('bearer', '', $token));

        if ($token === '') {
            return json(['code' => 0, 'msg' => '未提供令牌']);
        }

        $res = Jwt::Check($token);
        if ($res['code'] !== 1) {
            return json(['code' => 0, 'msg' => $res['msg']]);
        }

        // 将业务数据挂载到请求对象，供后续控制器使用
        $request->user = $res['data'];

        return $next($request);
    }
}