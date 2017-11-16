<?php

namespace Loytor\Wxhelper\Utils;

trait ErrorMsg
{
    protected $last_error_code;
    protected $last_error_msg;

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