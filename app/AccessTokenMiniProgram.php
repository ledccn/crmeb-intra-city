<?php

namespace Ledc\CrmebIntraCity;

use EasyWeChat\Core\Exceptions\HttpException;
use EasyWeChat\MiniProgram\AccessToken;

/**
 * 获取小程序稳定版接口调用凭据
 * @link https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/mp-access-token/getStableAccessToken.html
 */
class AccessTokenMiniProgram extends AccessToken
{
    /**
     * Get the access token from WeChat server.
     * @return array
     * @throws HttpException
     */
    public function getTokenFromServer(): array
    {
        $params = [
            'appid' => $this->appId,
            'secret' => $this->secret,
            'grant_type' => 'client_credential',
        ];

        $http = $this->getHttp();
        $token = $http->parseJSON($http->json(AccessTokenCore::API_STABLE_TOKEN_POST, $params));

        if (empty($token[$this->tokenJsonKey])) {
            throw new HttpException('Request Stable AccessToken fail. response: ' . json_encode($token, JSON_UNESCAPED_UNICODE));
        }

        return $token;
    }
}