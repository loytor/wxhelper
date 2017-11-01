<?php

namespace Loytor\Wxhelper\Notify;

use Loytor\Wxhelper\Exception\WechatException;

class WechatNotify
{
    //加密、解密使用
    private $_block_size = 32;

    //初始化的参数
    private $_token; //token
    private $_appid; //appid
    private $_EncodingAESKey; //EncodingAESKey

    //微信输入的信息
    private $_encrypt_type = 'raw';//可选的加密类型,默认是不加密
    private $_signature;//签名，用来进行来源验证
    private $_msg_signature;//可选的消息签名($_encrypt_type='aes'时，该参数必须存在)
    private $_timestamp;//消息时间戳
    private $_nonce;//随机数

    //处理后数据
    private $_postStr;//POST消息文本
    private $_msgStr;//解析或解密后的消息文本，明文模式与$_postStr相同
    private $_postData;//xml解析后数组对象
    private $_msgData;//解析或解密后的消息对象,明文模式与$_postData相同

    private $_replace_tpl = array();//输出替换模板 {{key}}=>value

    /**
     * 初始化微信消息库
     * @param $options
     * 必选：$options['token'] = token(如果是第三方授权，则为授权平台token)
     * 必选：$options['appid'] = appid(如果是第三方授权，则为授权平台component_id)
     * 必选：$options['gets'] = $_GET;
     * 必选：$options['contents'] = file_get_contents('php://input');
     * 可选：$options['EncodingAESKey'] = EncodingAESKey(如果是第三方授权，则为授权平台EncodingAESKey)
     * 可选：$options['auto_valid'] = 是否自动验证来源，默认true
     * @throws WechatException
     */
    public function __construct($options)
    {
        //验证get参数
        $gets = $options['gets'] ?? [];

        if (isset($gets['echostr'])) {//存在echostr输入，则直接返回以便通过微信后台接入验证
            exit($gets['echostr']);
        }
        //判断基本参数是否完全
        if (!isset($gets['timestamp']) || !isset($gets['nonce']) || !isset($gets['signature'])) {
            throw new WechatException('参数不全', -120);
        } else {
            $this->_timestamp = $gets['timestamp'];
            $this->_nonce = $gets['nonce'];
            $this->_signature = $gets['signature'];
        }
        //判断是否为加密消息
        if (isset($gets['encrypt_type']) && $gets['encrypt_type'] == 'aes') {//加密消息
            if (isset($gets['msg_signature'])) {
                $this->_encrypt_type = 'aes';
                $this->_msg_signature = $gets['msg_signature'];
            } else {//加密消息没有提供消息签名
                throw new WechatException('加密消息没有提供消息签名', -130);
            }
        }


        //token 必须输入
        if (isset($options['token'])) {
            $this->_token = $options['token'];
        } else {
            throw new WechatException('WechatMessage:token is null.', -120);
        }
        //appid 必须输入
        if (isset($options['appid'])) {
            $this->_appid = $options['appid'];
        } else {
            throw new WechatException('WechatMessage:appid is null.', -120);
        }
        if ($this->_encrypt_type == 'aes') {//加密消息，需要指定EncodingAESKey
            if (isset($options['EncodingAESKey'])) {
                $this->_EncodingAESKey = $options['EncodingAESKey'];
            } else {
                throw new WechatException('WechatMessage:EncodingAESKey is null.', -120);
            }
        }
        if (!isset($options['auto_valid']) || $options['auto_valid'] == true) {//自动验证来源(默认true)
            if (!$this->checkURLSignature()) {
                throw new WechatException('签名错误', -199);
            }
        }
        //获取微信服务器raw数据
        $this->_postStr = $options['contents'] ?? '';
        if (empty($this->_postStr)) {//没有信息进来
            throw new WechatException('contents', -120);
        }

        //解析xml_to_array
        $this->_postData = $this->xml_to_array($this->_postStr);
        if (empty($this->_postData)) {//解析xml错误
            throw new WechatException('解析xml错误', -199);
        }
        if ($this->_encrypt_type == 'aes') {//加密信息，进行解密
            if (isset($this->_postData['Encrypt'])
                //公众平台消息包含字段ToUserName，授权Ticket刷新包含字段AppId
                && (isset($this->_postData['ToUserName']) || isset($this->_postData['AppId']))
            ) {
                //1.获得加密的信息
                $encrypt_msg = $this->_postData['Encrypt'];
                //2.验证加密信息签名
                if ($this->signature($encrypt_msg, $this->_token, $this->_timestamp, $this->_nonce) == $this->_msg_signature) {
                    //3.解密
                    $result = $this->DecryptMsg($encrypt_msg, $this->_EncodingAESKey, $this->_block_size);
                    //4.判断初始化的appid和解密出来的appid是否相同
                    if (is_array($result) && $result['appid'] == $this->_appid) {
                        //4.得到明文
                        $this->_msgStr = $result['msg'];
                        //$this->_msgData = (array)simplexml_load_string($this->_msgStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                        $this->_msgData = $this->xml_to_array($this->_msgStr);
                        if (empty($this->_msgData)) {
                            throw new WechatException('解析_msgData错误', -199);
                        }
                    } else {
                        throw new WechatException('微信信息解密错误', -199);
                    }
                } else {//签名不正确
                    throw new WechatException('签名不正确', -199);
                }
            } else {//消息不完整
                throw new WechatException('消息不完整', -199);
            }
        } else {//明文信息
            $this->_msgData = $this->_postData;
            $this->_msgStr = $this->_postStr;
        }

        //数据校验
        if (isset($this->_postData['ToUserName'])) {//公众账号消息
            if (!isset($this->_msgData['ToUserName'])
                || $this->_postData['ToUserName'] != $this->_msgData['ToUserName']
            ) {
                throw new WechatException('公众账号消息解密数据参数不全', -200);
            }
        } elseif (isset($this->_postData['AppId'])) {//授权ticket消息
            if (!isset($this->_msgData['AppId'])
                || $this->_postData['AppId'] != $this->_msgData['AppId']
            ) {
                throw new WechatException('授权ticket消息解密数据参数不全', -200);
            }
        } else {
            throw new WechatException('数据校验失败', -200);
        }
    }

    /*
     * 构造指定长度的随机字符串
     */
    private function _getRandomStr($size = 16)
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
     * 构造回复加密信息结构的消息XML文本
     */
    private function _build_encrypt_xml($encrypt, $signature, $timestamp, $nonce)
    {
        $format = '<xml><Encrypt><![CDATA[%s]]></Encrypt><MsgSignature><![CDATA[%s]]></MsgSignature><TimeStamp>%s</TimeStamp><Nonce><![CDATA[%s]]></Nonce></xml>';
        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }

    /*
     * 检测消息类型
     */
    private function _checkMsgType($type)
    {
        return ($this->getMsgType() == $type);
    }

    /*
     * 检测事件类型
     */
    private function _checkEventType($type)
    {
        if ($this->_checkMsgType('event')) {
            return ($this->getEventType() == $type);
        }
        return false;
    }

    /**
     * 添加一个替换模板 {{key}}=>value
     * @param array $tpl_array
     */
    public function addTpl(array $tpl_array)
    {
        $this->_replace_tpl = array_merge($this->_replace_tpl, $tpl_array);
    }

    /*
     * 返回原始POST的数据字符串
     */
    public function getPostStr()
    {
        return $this->_postStr;
    }

    /*
     * 返回原始的POST数据对象
     */
    public function getPostData()
    {
        return $this->_postData;
    }

    /*
     * 返回解密后的数据字符串
     */
    public function getMsgStr()
    {
        return $this->_msgStr;
    }

    /*
     * 返回解密后的数据对象
     */
    public function getMsgData()
    {
        return $this->_msgData;
    }

    /*----------------------------------------------------
        获取消息的指定字段
    ------------------------------------------------------*/
    public function getInfoType()
    {
        return isset($this->_msgData['InfoType']) ? $this->_msgData['InfoType'] : '';
    }


    public function getFromUserName()
    {
        return isset($this->_msgData['FromUserName']) ? $this->_msgData['FromUserName'] : '';
    }

    public function getToUserName()
    {
        return isset($this->_msgData['ToUserName']) ? $this->_msgData['ToUserName'] : '';
    }

    public function getMsgType()
    {
        return $this->_msgData['MsgType'];
    }

    public function getEventType()
    {
        return isset($this->_msgData['Event']) ? $this->_msgData['Event'] : '';
    }

    public function getEventKey()
    {
        return isset($this->_msgData['EventKey']) ? (is_string($this->_msgData['EventKey']) ? $this->_msgData['EventKey'] : '') : '';
    }

    public function getMsgId()
    {
        return isset($this->_msgData['MsgId']) ? $this->_msgData['MsgId'] : '';
    }

    /**
     * 获取扫码Ticket字段
     * @return string
     */
    public function getScanTicket()
    {
        return isset($this->_msgData['Ticket']) ? $this->_msgData['Ticket'] : '';
    }

    /**
     * 返回群发消息回报或模板消息回报的消息ID
     * @return string
     */
    public function getEventMsgId()
    {
        return isset($this->_msgData['MsgID']) ? $this->_msgData['MsgID'] : '';
    }

    public function getEventStatus()
    {
        return isset($this->_msgData['Status']) ? $this->_msgData['Status'] : '';
    }

    public function getContent()
    {
        return isset($this->_msgData['Content']) ? $this->_msgData['Content'] : '';
    }

    /*----------------------------------------------------
        检测消息是否为指定的消息类型
    ------------------------------------------------------*/
    public function isText()
    {
        return $this->_checkMsgType('text');
    }

    public function isImage()
    {
        return $this->_checkMsgType('image');
    }

    public function isLink()
    {
        return $this->_checkMsgType('link');
    }

    public function isLocation()
    {
        return $this->_checkMsgType('location');
    }

    public function isShortVideo()
    {
        return $this->_checkMsgType('shortvideo');
    }

    public function isVideo()
    {
        return $this->_checkMsgType('video');
    }

    public function isVoice()
    {
        return $this->_checkMsgType('voice');
    }

    public function isEvent()
    {
        return $this->_checkMsgType('event');
    }

    /*----------------------------------------------------
        检测消息是否为指定的事件消息类型
    ------------------------------------------------------*/
    public function isEvent_Subscribe()
    {
        return $this->_checkEventType('subscribe');
    }

    public function isEvent_Unsubscribe()
    {
        return $this->_checkEventType('unsubscribe');
    }

    /**
     * 是否为扫码事件（包括了扫码关注）
     * @return bool
     */
    public function isEvent_Scan()
    {
        if ($this->_checkEventType('SCAN')
            || ($this->_checkEventType('subscribe') && strpos($this->getEventKey(), 'qrscene_') === 0)
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function isEvent_Location()
    {
        return $this->_checkEventType('LOCATION');
    }

    public function isEvent_Click()
    {
        return $this->_checkEventType('CLICK');
    }

    public function isEvent_View()
    {
        return $this->_checkEventType('VIEW');
    }

    public function isEvent_MassSendJobFinish()
    {
        return $this->_checkEventType('MASSSENDJOBFINISH');
    }

    public function isEvent_TemplateSendFinish()
    {
        return $this->_checkEventType('TEMPLATESENDJOBFINISH');
    }

    private function _xmlSafeStr($str)
    {
        return '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $str) . ']]>';
    }

    /**
     * 把一个对象转化为xml文本
     * @param $data
     * @param string $root_key
     * @param string $item_key
     * @return string
     */
    public function data_to_xml($data, $root_key = '', $item_key = '')
    {
        $xml = '';
        foreach ($data as $key => $val) {

            if ($key == '@attributes') {
                continue;
            }

            $flg = false;
            if (is_array($val) && array_values($val) === $val) {
                $flg = true;
            } else {
                if (!empty($item_key)) {
                    $key = $item_key;
                }
                $xml .= "<$key>";
            }
            $xml .= (is_array($val) || is_object($val)) ? $this->data_to_xml($val, '', $flg ? $key : '') : $this->_xmlSafeStr($val);
            if ($flg) {

            } else {
                $xml .= "</$key>";
            }
        }
        if (!empty($root_key)) {
            $xml = "<$root_key>" . $xml . "</$root_key>";
        }
        return $xml;
    }

    /**
     * 把xml文本转换成数组对象
     * @param $xml_str
     * @return mixed
     */
    public function xml_to_array($xml_str)
    {
        return json_decode(json_encode(simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA)), TRUE);
    }

    /**
     * 回复消息
     * @param string $msg_xml_string XML格式的消息文本
     * @return string
     */
    public function reply($msg_xml_string)
    {
        if ($this->_encrypt_type == 'aes') {
            $encrypted = $this->EncryptMsg($msg_xml_string, $this->_appid, $this->_EncodingAESKey, $this->_block_size);
            $timeStamp = time();
            //签名
            $signature = $this->signature($encrypted, $this->_token, $timeStamp, $this->_nonce);
            $msg_xml_string = $this->_build_encrypt_xml($encrypted, $signature, $timeStamp, $this->_nonce);
        }
        return $msg_xml_string;
    }

    /**
     * 回复文本内容
     * @param string $content 文本内同
     * @param null $from_username 可选
     * @param null $to_username 可选
     * @return string
     */
    public function reply_text($content, $from_username = null, $to_username = null)
    {
        $template = <<<MSG
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>
MSG;
        $xml_str = sprintf($template,
            ($to_username ?: $this->_msgData['FromUserName']),
            ($from_username ?: $this->_msgData['ToUserName']),
            time(),
            empty($content) ? '<空回复>' : $content
        );

        if (count($this->_replace_tpl)) {//指定模板替换
            $xml_str = strtr($xml_str, $this->_replace_tpl);
        }

        return $this->reply($xml_str);
    }



    //________________________________________________________________Mq

    /**
     * 回复图片内容
     * @param int $mediaid 媒体id必须
     * @param null $from_username 可选
     * @param null $to_username 可选
     * @return string
     */
    public function reply_image($mediaid, $from_username = null, $to_username = null)
    {
        if (empty($mediaid) || false == is_string($mediaid)) {
            throw new WechatException('WechatMessage:mediaid error.' . $mediaid, -301);
        }

        $template = <<<MSG
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[%s]]></MediaId>
</Image>
</xml>
MSG;
        $xml_str = sprintf($template,
            ($to_username ?: $this->_msgData['FromUserName']),
            ($from_username ?: $this->_msgData['ToUserName']),
            time(),
            $mediaid
        );
        return $this->reply($xml_str);
    }

    /**
     * 回复语音内容
     * @param int $mediaid 媒体id必须
     * @param null $from_username 可选
     * @param null $to_username 可选
     * @return string
     */
    public function reply_voice($mediaid, $from_username = null, $to_username = null)
    {
        if (empty($mediaid) || false == is_string($mediaid)) {
            throw new WechatException('WechatMessage:mediaid error.' . $mediaid, -302);
        }

        $template = <<<MSG
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[voice]]></MsgType>
<Voice>
<MediaId><![CDATA[%s]]></MediaId>
</Voice>
</xml>
MSG;
        $xml_str = sprintf($template,
            ($to_username ?: $this->_msgData['FromUserName']),
            ($from_username ?: $this->_msgData['ToUserName']),
            time(),
            $mediaid
        );
        return $this->reply($xml_str);
    }

    /**
     * 回复视频内容
     * @param int $mediaid 媒体id必须
     * @param array $array 可选
     * @param null $from_username 可选
     * @param null $to_username 可选
     * @return string
     */
    public function reply_video($mediaid, $array = array(), $from_username = null, $to_username = null)
    {
        if (empty($mediaid) || false == is_string($mediaid)) {
            throw new WechatException('WechatMessage:mediaid error.' . $mediaid, -303);
        }

        $template = <<<MSG
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[video]]></MsgType>
<Video>
<MediaId><![CDATA[%s]]></MediaId>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
</Video>
</xml>
MSG;
        $xml_str = sprintf($template,
            ($to_username ?: $this->_msgData['FromUserName']),
            ($from_username ?: $this->_msgData['ToUserName']),
            time(),
            $mediaid,
            isset($array['title']) ? $array['title'] : '',
            isset($array['description']) ? $array['description'] : ''
        );
        return $this->reply($xml_str);
    }

    /**
     * 回复音乐内容
     * @param array $array 可选
     * @param null $from_username 可选
     * @param null $to_username 可选
     * @return string
     */
    public function reply_music($array = array(), $from_username = null, $to_username = null)
    {
        $template = <<<MSG
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
<Music>
<MediaId><![CDATA[%s]]></MediaId>
<Description><![CDATA[%s]]></Description>
<MusicUrl><![CDATA[%s]]></MusicUrl>
<HQMusicUrl><![CDATA[%]]></HQMusicUrl>
<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
</Music>
</xml>
MSG;
        $xml_str = sprintf($template,
            ($to_username ?: $this->_msgData['FromUserName']),
            ($from_username ?: $this->_msgData['ToUserName']),
            time(),
            isset($array['title']) ? $array['title'] : '',
            isset($array['description']) ? $array['description'] : '',
            isset($array['hq_music_url']) ? $array['hq_music_url'] : '',
            isset($array['mediaid']) ? $array['mediaid'] : ''
        );
        return $this->reply($xml_str);
    }


    /**
     * 回复图文内容
     * @param array $articles 长度在1-10
     * @param null $from_username 可选
     * @param null $to_username 可选
     * @return string
     */
    public function reply_news($articles, $from_username = null, $to_username = null)
    {
        if (false == is_array($articles)) {
            throw new WechatException('WechatMessage:articles is not array.', -300);
        }

        $count = count($articles);
        if ($count > 8 || $count == 0) {
            throw new WechatException('WechatMessage:articles length not in(1,10)', -300);
        }

        $template = <<<MSG
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>
MSG;
        $xml_str = sprintf($template,
            ($to_username ?: $this->_msgData['FromUserName']),
            ($from_username ?: $this->_msgData['ToUserName']),
            time(),
            $count
        );

        $textTpl = '';
        foreach ($articles as $k => $v) {
            $title = isset($v['title']) ? $v['title'] : '';
            $description = isset($v['description']) ? $v['description'] : '';
            $picurl = isset($v['picurl']) ? $v['picurl'] : '';
            $url = isset($v['url']) ? $v['url'] : '';
            $textTpl .= "
            <item>
                 <Title><![CDATA[" . $title . "]]></Title> 
                 <Description><![CDATA[" . $description . "]]></Description>
                 <PicUrl><![CDATA[" . $picurl . "]]></PicUrl>
                 <Url><![CDATA[" . $url . "]]></Url>
            </item>
            ";
        }
        $textTpl .= "            
                 </Articles>
             </xml>
        ";
        $xml_str = $xml_str . $textTpl;

        if (count($this->_replace_tpl)) {//指定模板替换
            $xml_str = strtr($xml_str, $this->_replace_tpl);
        }

        return $this->reply($xml_str);
    }
    //________________________________________________________________Mq


    /**
     * 加密
     * @param string $reply_msg
     * @param string $appid
     * @param string $encodingAesKey
     * @param string $block_size
     * @return bool|string
     */
    public function EncryptMsg($reply_msg, $appid, $encodingAesKey, $block_size)
    {
        try {
            $key = base64_decode($encodingAesKey . "=");
            //获得16位随机字符串，填充到明文之前
            $random = $this->_getRandomStr();
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
            return false;
        }
    }


    /**
     * 解密
     * @param string $encrypt_msg 密文
     * @param string $encodingAesKey
     * @param string $block_size
     * @return bool|array('appid','msg')
     */
    public function DecryptMsg($encrypt_msg, $encodingAesKey, $block_size)
    {
        try {
            $key = base64_decode($encodingAesKey . "=");
            $iv = substr($key, 0, 16);
            $decrypted = openssl_decrypt($encrypt_msg, 'AES-256-CBC', substr($key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
        } catch (\Exception $e) {
            throw new WechatException("wechatMessage:error1" . $e->getMessage(), -302);
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
            throw new WechatException("wechatMessage:Decrypt2" . $e->getMessage(), -302);
        }
        return array(
            'appid' => $from_corpid,
            'msg' => $xml_content
        );
    }

    /**
     * 验证微信请求签名是否为微信消息
     * @return bool
     */
    public function checkURLSignature()
    {
        $tmpStr = $this->signature($this->_token, $this->_timestamp, $this->_nonce);
        if ($tmpStr == $this->_signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 签名(参数可变)
     * @return bool|string
     */
    public function signature()
    {
        //排序
        try {
            $array = func_get_args();
            sort($array, SORT_STRING);
            $str = implode($array);
            return sha1($str);
        } catch (WechatException $e) {
            return false;
        }
    }
}