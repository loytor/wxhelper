<?php

namespace Loytor\Wxhelper\Message;

interface EntityInterface
{
    public function getPostStr();#原始消息体: str

    public function getPostData();#原始消息体:array

    public function getMsgStr();#如果是aes密文,解密后的消息体:str

    public function getMsgData();#如果是aes密文,解密后的消息体:str

    public function getMsgType();#消息体类型

    public function getEventType();#消息事件类型

    public function getEventKey();#消息事件 key

    public function getMsgId();#消息 id

    public function getScanTicket();#扫码 ticket

    public function getEventStatus();#事件状态

    public function getContent();#文本消息内容

    public function getInfoType();#消息简要类型

    public function getFromUserName();#消息的发送人

    public function getToUserName();#消息的接受公众号
}