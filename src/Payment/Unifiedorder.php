<?php

namespace Loytor\Wxhelper\Payment;

use Loytor\Wxhelper\AbstractPayment;
use Loytor\Wxhelper\Exception\WechatException;

class Unifiedorder extends AbstractPayment
{
    /**
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1.
     */
    const REQUEST_BASE = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * 商户 KEY.
     */
    protected $key;

    /**
     * 有效的 trade_type 类型.
     */
    protected $tradeTypes = ['JSAPI', 'NATIVE', 'APP', 'WAP'];

    /**
     * 组织订单需要的参数
     */
    protected $elements = [];

    /**
     * 构造方法.
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * 获取商户 Key.
     */
    public function getKey()
    {
        return $this->key;
    }

    public function getElements()
    {
        return $this->elements;
    }

    /**
     * 获取响应结果.
     */
    public function execute()
    {
        $options = $this->getElements();
        ksort($options);
        $options['sign'] = strtoupper(md5(urldecode(http_build_query($options)) . '&key=' . $this->key));

        $result = $this->_postXmlData(self::REQUEST_BASE, $options);
        return $result;
    }

    public function getPaySignParams(){

    }

    public function set(string $name, $value)
    {
        $this->elements[$name] = $value;
        return $this;
    }

    public function get(string $name)
    {
        return $this->elements[$name];
    }

    /**
     * 全部选项（不包括 sign）.
     */
    protected $defined = ['appid', 'mch_id', 'device_info', 'nonce_str', 'body', 'detail', 'attach', 'out_trade_no', 'fee_type', 'total_fee', 'spbill_create_ip', 'time_start', 'time_expire', 'goods_tag', 'notify_url', 'trade_type', 'product_id', 'limit_pay', 'openid'];

    /**
     * 必填选项（不包括 sign）.
     */
    protected $required = ['appid', 'mch_id', 'nonce_str', 'body', 'out_trade_no', 'total_fee', 'spbill_create_ip', 'notify_url', 'trade_type'];
}
