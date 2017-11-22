<?php

namespace Loytor\Wxhelper\Message\Event;

use Loytor\Wxhelper\Message\EventAbstract;

class EventUnSubscribe extends EventAbstract
{
    public function isValid()
    {
        return Event::instance($this->entity)->isValid() && $this->entity->getEventType() === 'unsubscribe';
    }
}