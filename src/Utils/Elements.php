<?php

namespace Loytor\Wxhelper\Utils;

trait Elements
{
    public $elements;

    public function set(string $key, $value)
    {
        $this->elements[$key] = $value;
        return $this;
    }

    public function get(string $key)
    {
        return $this->elements[$key] ?? '';
    }

    public function gets()
    {
        return $this->elements;
    }

    public function getXml()
    {
        return Serializer::array2Xml($this->gets());
    }

    public function getCDATAXml()
    {
        return Serializer::array2Xml($this->gets(), 0, 0, 1);
    }
}