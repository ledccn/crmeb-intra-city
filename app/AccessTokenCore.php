<?php

namespace Ledc\CrmebIntraCity;

use EasyWeChat\Core\AccessToken;
use EasyWeChat\Core\Exceptions\HttpException;

/**
 * 获取稳定版接口调用凭据
 * @link https://developers.weixin.qq.com/doc/offiaccount/Basic_Information/getStableAccessToken.html
 */
class AccessTokenCore extends AccessToken
{
    /**
     * 接口地址
     */
    public const API_STABLE_TOKEN_POST = 'https://api.weixin.qq.com/cgi-bin/stable_token';

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
        $token = $http->parseJSON($http->json(self::API_STABLE_TOKEN_POST, $params));

        if (empty($token[$this->tokenJsonKey])) {
            throw new HttpException('Request Stable AccessToken fail. response: ' . json_encode($token, JSON_UNESCAPED_UNICODE));
        }

        return $token;
    }
}
