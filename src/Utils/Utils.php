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

    /**
     * 对url进行签名，以便前端脚本使用
     * @param string $url
     * @param string $ticket
     * @param string $type =jsapi/wxcard
     * @return array
     */
    public function getTicketSignature($url, $ticket, $type = 'jsapi')
    {
        #去掉#号部分
        $url = explode('#', $url);
        $url = $url[0];

        $timestamp = time();
        $nonceStr = str_random(16);

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            'appId' => $this->authorizer_appid,
            'nonceStr' => $nonceStr,
            'timestamp' => $timestamp,
            'signature' => $signature,
        );
        return $signPackage;
    }
}