<?php

namespace Loytor\Wxhelper\Message;

use Loytor\Wxhelper\Exception\WechatException;
use Loytor\Wxhelper\Utils\Serializer;
use Loytor\Wxhelper\Utils\Utils;
use Symfony\Component\HttpFoundation\Request;

class Entity implements EntityInterface
{
    public $request;
    public $component_appid, $component_key, $component_token;#初始化的参数
    public $msg_data, $msg_str;#消息体,数组 和 string.xml
    public $post_data, $post_str;#原始的消息体

    public function __construct(array $options)
    {
        $this->request = Request::createFromGlobals();
        $this->_initialize($options);
    }

    private function _initialize($options)
    {
        #检查系统参数
        $this->component_key = $options['component_key'] ?? '';
        $this->component_appid = $options['component_appid'] ?? '';
        $this->component_token = $options['component_token'] ?? '';
        if (!$this->component_key || !$this->component_appid || !$this->component_token) {
            throw new WechatException('component参数错误', 10001);
        }

        #存在echostr输入，则直接返回以便通过微信后台接入验证
        $echostr = $this->request->get('echostr');
        if (!is_null($echostr)) {
            exit($echostr);
        }

        #判断基本参数是否完全
        $timestamp = $this->request->get('timestamp');
        $nonce = $this->request->get('nonce');
        $signature = $this->request->get('signature');
        if (is_null($timestamp) || is_null($nonce) || is_null($signature)) {
            throw new WechatException('参数不全', 10000);
        }

        #判断是否为加密消息
        $encrypt_type = $this->request->get('encrypt_type');#aes
        $msg_signature = $this->request->get('msg_signature');#签名

        #检查 postRaw 参数
        $this->post_str = $this->request->getContent();
        if (!$this->post_str) {
            throw new WechatException('没有微信消息', 70000);
        }
        $this->post_data = @Serializer::xml2Array($this->post_str);

        #初始化为明文消息
        $msg_data = $this->post_data;
        $msg_str = $this->post_str;
        if ($encrypt_type == 'aes') {#如果是加密信息
            if (isset($this->post_data['Encrypt'])
                #公众平台消息包含字段ToUserName，授权Ticket刷新包含字段AppId
                && (isset($this->post_data['ToUserName']) || isset($this->post_data['AppId']))
            ) {
                #1.获得加密的信息
                $encrypt_msg = $this->post_data['Encrypt'];

                #2.验证加密信息签名
                if (Utils::signature($encrypt_msg, $this->component_token, $timestamp, $nonce) == $msg_signature) {
                    #3.解密
                    $result = Utils::DecryptMsg($encrypt_msg, $this->component_key, 32);
                    #4.判断初始化的appid和解密出来的appid是否相同
                    if (is_array($result) && $result['appid'] == $this->component_appid) {
                        //4.得到明文
                        $msg_str = $result['msg'];
                        $msg_data = Serializer::xml2Array($msg_str);
                        if (empty($msg_data)) {
                            throw new WechatException('消息解密失败', 70001);
                        }
                    } else {
                        throw new WechatException('appid不对应', 70002);
                    }
                } else {//签名不正确
                    throw new WechatException('微信消息签名验证失败', 70003);
                }
            }
        }

        #把需要用到的参数全部塞进全局变量中
        $this->msg_data = $msg_data;
        $this->msg_str = $msg_str;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getPostStr()
    {
        return $this->post_str;
    }

    public function getPostData()
    {
        return $this->post_data;
    }

    public function getMsgStr()
    {
        return $this->msg_str;
    }

    public function getMsgData()
    {
        return $this->msg_data;
    }

    public function getMsgType()
    {
        return $this->getMsgData()['MsgType'] ?? '';
    }

    public function getEventType()
    {
        return $this->getMsgData()['Event'] ?? '';
    }


    public function getEventKey()
    {
        return $this->getMsgData()['EventKey'] ?? '';
    }

    public function getMsgId()
    {
        return $this->getMsgData()['MsgId'] ?? '';
    }

    /**
     * 获取扫码Ticket字段
     * @return string
     */
    public function getScanTicket()
    {
        return $this->getMsgData()['Ticket'] ?? '';
    }

    /**
     * 返回群发消息回报或模板消息回报的消息ID
     * @return string
     */
    public function getEventStatus()
    {
        return $this->getMsgData()['Status'] ?? '';
    }

    public function getContent()
    {
        return $this->getMsgData()['Content'] ?? '';
    }

    public function getFromUserName()
    {
        return $this->getMsgData()['FromUserName'] ?? '';
    }

    public function getInfoType()
    {
        return $this->getMsgData()['InfoType'] ?? '';
    }

    public function getToUserName()
    {
        return $this->getMsgData()['ToUserName'] ?? '';
    }

}