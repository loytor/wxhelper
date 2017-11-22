<?php

namespace Loytor\Wxhelper\Message;

use Loytor\Wxhelper\Exception\WechatException;
use Loytor\Wxhelper\Utils\Elements;
use Loytor\Wxhelper\Utils\Utils;

class Reply
{
    use Elements;
    protected $entity;
    protected $authorizer_appid;

    public function __construct(Entity $entity, $authorizer_appid)
    {
        $this->entity = $entity;
        $this->authorizer_appid = $authorizer_appid;
    }

    public static function instance(Entity $entity, string $authorizer_appid)
    {
        return new static($entity, $authorizer_appid);
    }

    private function respond()
    {
        $xml = $this->getCDATAXml();
        $xmls = explode("\n", $xml);
        $xml_no_header = $xmls[1] ?? '';
        if (!$xml_no_header) {
            throw new WechatException('xml 格式错误, XML:' . $xml, 70000);
        }
        return Utils::EncryptMsg($xml_no_header, $this->authorizer_appid, $this->entity->component_key, 32);
    }

    private function init(string $msg_type)
    {
        $this->set('ToUserName', $this->entity->getFromUserName());
        $this->set('FromUserName', $this->entity->getToUserName());
        $this->set('CreateTime', time());
        $this->set('MsgType', $msg_type);
    }

    public function text(string $Content)
    {
        $this->init('text');
        $this->set('Content', $Content);
        return $this->respond();
    }

    public function image($media_id)
    {
        $this->init('image');
        $this->set('Image', ['MediaId' => $media_id]);
        return $this->respond();
    }

    public function voice($media_id)
    {
        $this->init('voice');
        $this->set('Voice', ['MediaId' => $media_id]);
        return $this->respond();
    }

    public function video($media_id, $title, $description)
    {
        $this->init('video');
        $this->set('Video', [
            'MediaId' => $media_id,
            'Title' => $title,
            'Description' => $description,
        ]);
        return $this->respond();
    }

    public function news(array $articles)
    {
        $this->init('news');
        $this->set('ArticleCount', count($articles));
        $this->set('Articles', $articles);
        return $this->respond();
    }
}