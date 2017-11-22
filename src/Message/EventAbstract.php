<?php

namespace Loytor\Wxhelper\Message;

abstract class EventAbstract
{
    public $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    public static function instance(Entity $entity)
    {
        return new static($entity);
    }

    abstract public function isValid();
}