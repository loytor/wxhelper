<?php

namespace Loytor\Wxhelper\Utils;

class Utils
{
    public static function createPaymentSign(array $params, string $key)
    {
        ksort($params);
        $sign = strtoupper(md5(urldecode(http_build_query($params)) . '&key=' . $key));
        return $sign;
    }
}