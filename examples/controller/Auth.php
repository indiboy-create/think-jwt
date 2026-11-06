<?php

declare(strict_types=1);

namespace app\controller;

use think\facade\Jwt;
use think\Response;

/**
 * 令牌签发 / 校验 / 刷新 / 注销 示例
 */
class Auth
{
    /**
     * 签发令牌
     */
    public function create(): Response
    {
        $token = Jwt::getToken([
            'user_id'  => 12,
            'username' => 'youvision',
        ]);

        return json([
            'code' => 1,
            'msg'  => '签发成功',
            'data' => ['token' => $token],
        ]);
    }

    /**
     * 校验令牌
     */
    public function check(): Response
    {
        $token = Jwt::getRequestHeaders();
        if ($token === '') {
            return json(['code' => 0, 'msg' => '未提供令牌']);
        }

        $res = Jwt::Check($token);
        if ($res['code'] !== 1) {
            return json(['code' => 0, 'msg' => $res['msg']]);
        }

        return json([
            'code' => 1,
            'msg'  => '校验通过',
            'data' => $res['data'],
        ]);
    }

    /**
     * 刷新令牌
     */
    public function refresh(): Response
    {
        $res = Jwt::Refresh(Jwt::getRequestHeaders());

        return json($res);
    }

    /**
     * 注销令牌
     */
    public function logout(): Response
    {
        $res = Jwt::Logout(Jwt::getRequestHeaders());

        return json($res);
    }
}