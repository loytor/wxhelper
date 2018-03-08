<?php

namespace Loytor\Wxhelper\Payment;

class TradeCallback
{
    /**
     * @param string $return_code SUCCESS : FAIL
     * @param string $return_msg 非空表示返回给微信支付的错误信息
     * @return string
     */
    public static function getXml($return_code = "SUCCESS", $return_msg = "OK")
    {
        return "<xml><return_code><![CDATA[{$return_code}]]></return_code><return_msg><![CDATA[{$return_msg}]]></return_msg></xml>";
    }
}
