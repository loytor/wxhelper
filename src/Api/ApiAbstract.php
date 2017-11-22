<?php

namespace Loytor\Wxhelper\Api;

abstract class ApiAbstract
{
    protected $access_token;
    protected $appid;
    protected $last_error_code;
    protected $last_error_msg;

    public function __construct($access_token, $appid)
    {
        $this->access_token = $access_token;
        $this->appid = $appid;
    }

    public static function instance($access_token, $appid)
    {
        return new static($access_token, $appid);
    }

    public function setErrorMsg($resp)
    {
        $this->last_error_code = $resp['errcode'] ?? 0;
        $this->last_error_msg = $resp['errmsg'] ?? '';
    }

    public function getErrorMsg()
    {
        return [
            'errcode' => $this->last_error_code,
            'errmsg' => $this->last_error_msg,
        ];
    }
}