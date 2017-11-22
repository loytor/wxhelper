<?php

namespace Loytor\Wxhelper\Utils;

use Loytor\Wxhelper\Exception\WechatException;

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
    public static function getTicketSignature($authorizer_appid, $url, $ticket, $type = 'jsapi')
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
            'appId' => $authorizer_appid,
            'nonceStr' => $nonceStr,
            'timestamp' => $timestamp,
            'signature' => $signature,
        );
        return $signPackage;
    }

    /**
     * 加密
     * @param string $reply_msg
     * @param string $appid
     * @param string $encodingAesKey
     * @param string $block_size
     * @return bool|string
     */
    public static function EncryptMsg($reply_msg, $appid, $encodingAesKey, $block_size)
    {
        try {
            $key = base64_decode($encodingAesKey . "=");
            //获得16位随机字符串，填充到明文之前
            $random = static::getRandomStr();
            $text = $random . pack("N", strlen($reply_msg)) . $reply_msg . $appid;
            $iv = substr($key, 0, 16);

            /*字符串补位*/
            $text_length = strlen($text);
            //计算需要填充的位数
            $amount_to_pad = $block_size - ($text_length % $block_size);
            if ($amount_to_pad == 0) {
                $amount_to_pad = $block_size;
            }
            //获得补位所用的字符
            $pad_chr = chr($amount_to_pad);
            $tmp = "";
            for ($index = 0; $index < $amount_to_pad; $index++) {
                $tmp .= $pad_chr;
            }
            $text = $text . $tmp;

            $encrypted = openssl_encrypt($text, 'AES-256-CBC', substr($key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
            return $encrypted;
        } catch (\Exception $e) {
            throw new WechatException($e->getMessage());
        }
    }


    /**
     * 解密
     * @param string $encrypt_msg 密文
     * @param string $encodingAesKey
     * @param string $block_size
     * @return bool|array('appid','msg')
     */
    public static function DecryptMsg($encrypt_msg, $encodingAesKey, $block_size)
    {
        try {
            $key = base64_decode($encodingAesKey . "=");
            $iv = substr($key, 0, 16);
            $decrypted = openssl_decrypt($encrypt_msg, 'AES-256-CBC', substr($key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
        } catch (\Exception $e) {
            throw new WechatException($e->getMessage());
        }
        try {
            //去除补位字符
            $pad = ord(substr($decrypted, -1));
            if ($pad < 1 || $pad > $block_size) {
                $pad = 0;
            }
            $result = substr($decrypted, 0, (strlen($decrypted) - $pad));

            //去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16) {
                return "";
            }
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_corpid = substr($content, $xml_len + 4);
        } catch (\Exception $e) {
            throw new WechatException($e->getMessage());
        }
        return array(
            'appid' => $from_corpid,
            'msg' => $xml_content
        );
    }

    /*
     * 构造指定长度的随机字符串
     */
    public static function getRandomStr($size = 16)
    {
        $str = '';
        $str_pol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < $size; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * 签名(参数可变)
     * @return bool|string
     */
    public static function signature()
    {
        //排序
        $array = func_get_args();
        sort($array, SORT_STRING);
        $str = implode($array);
        return sha1($str);
    }
}