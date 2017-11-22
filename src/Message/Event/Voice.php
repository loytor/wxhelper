<?php

namespace Thenbsp\Wechat\Event\Event;

use Loytor\Wxhelper\Message\EventAbstract;

class Voice extends EventAbstract
{
    public function isValid()
    {
        return $this->entity->getMsgType() === 'voice';
    }
}
