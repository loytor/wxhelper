<?php

namespace Loytor\Wxhelper\Message;

use Symfony\Component\HttpFoundation\Request;

abstract class Entity
{
    public $request;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
    }
}