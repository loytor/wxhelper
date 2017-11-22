<?php

namespace Loytor\Wxhelper\Message\Event;

use Loytor\Wxhelper\Message\EventAbstract;

class EventMassSendJobFinish extends EventAbstract
{
    public function isValid()
    {
        return Event::instance($this->entity)->isValid() && $this->entity->getEventType() === 'MASSSENDJOBFINISH';
    }
}