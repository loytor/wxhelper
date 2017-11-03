<?php

namespace Loytor\Wxhelper;


abstract class AbstractWechat extends AbstractBase
{
    private $authorizer_appid;
    private $authorizer_access_token;

    public function __construct(string $authorizer_access_token, string $authorizer_appid='')
    {
        $this->authorizer_appid = $authorizer_appid;
        $this->authorizer_access_token = $authorizer_access_token;
    }

    public function getAuthorizerAppid(){
        return $this->authorizer_appid;
    }

    public function getAuthorizerAccessToken(){
        return $this->authorizer_access_token;
    }

    public function getRequestUrl($request_base)
    {
        return $request_base . "?access_token={$this->getAuthorizerAccessToken()}";
    }
}