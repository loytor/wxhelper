<?php

namespace Loytor\Wxhelper\Message\Event;

use Loytor\Wxhelper\Message\EventAbstract;

/**
 * 扫码事件,包含关注扫码
 *
 * Class EventScan
 * @package Loytor\Wxhelper\Message\Event
 */
class EventScan extends EventAbstract
{
    public function isValid()
    {
        return Event::instance($this->entity)->isValid()
            && (($this->entity->getEventType() === 'SCAN') || ($this->entity->getEventType() === 'subscribe'))
            && (strpos($this->entity->getEventKey(), 'qrscene_') === 0);
    }
}