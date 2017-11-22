<?php

namespace Thenbsp\Wechat\Event\Event;

use Loytor\Wxhelper\Message\EventAbstract;

class ShortVideo extends EventAbstract
{
    public function isValid()
    {
        return $this->entity->getMsgType() === 'shortvideo';
    }
}
