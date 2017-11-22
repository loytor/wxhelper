<?php

namespace Loytor\Wxhelper\Message\Event;

use Loytor\Wxhelper\Message\EventAbstract;

class Image extends EventAbstract
{
    public function isValid()
    {
        return $this->entity->getMsgType() === 'image';
    }
}