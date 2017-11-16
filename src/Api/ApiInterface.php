<?php

namespace Loytor\Wxhelper\Api;


interface ApiInterface
{
    public static function initialize($access_token, $appid);

    public function getErrorMsg();

    public function setErrorMsg($arr_resp);

}