<?php

namespace Loytor\Wxhelper\Message\Event;

use Loytor\Wxhelper\Message\EventAbstract;

class Text extends EventAbstract
{
    public function isValid()
    {
        return $this->entity->getMsgType() === 'text';
    }
}