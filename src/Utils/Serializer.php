<?php

namespace Loytor\Wxhelper\Utils;

class Serializer
{
    static function array2Xml($arr, $dom = 0, $item = 0, $cdata = 0)
    {
        if (!$dom) {
            $dom = new \DOMDocument("1.0");
        }
        if (!$item) {
            $item = $dom->createElement("xml");
            $dom->appendChild($item);
        }
        foreach ($arr as $key => $val) {
            $itemx = $dom->createElement(is_string($key) ? $key : "item");
            $item->appendChild($itemx);
            if (!is_array($val)) {
                if ($cdata && !is_numeric($val)) {
                    $text = $dom->createCDATASection($val);
                } else {
                    $text = $dom->createTextNode($val);
                }
                $itemx->appendChild($text);
            } else {
                static::array2Xml($val, $dom, $itemx, $cdata);
            }
        }
        $xml = $dom->saveXML();
        return $xml;
    }

    public static function xml2Array($xml = "")
    {
        $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $resp = json_decode(json_encode($xml), TRUE);
        return $resp;
    }

    public static function json2Array($json)
    {
        return json_decode($json, true);
    }

    public static function array2Json($arr)
    {
        return json_encode($arr);
    }

    /**
     * xml/json to array.
     */
    public static function parse($string)
    {
        if (static::isJSON($string)) {
            $result = self::json2Array($string);
        } elseif (static::isXML($string)) {
            $result = self::xml2Array($string);
        } else {
            throw new \InvalidArgumentException(sprintf('Unable to parse: %s', (string)$string));
        }

        return (array)$result;
    }

    /**
     * check is json string.
     */
    public static function isJSON($data)
    {
        return null !== @json_decode($data);
    }

    /**
     * check is xml string.
     */
    public static function isXML($data)
    {
        $xml = @simplexml_load_string($data);

        return $xml instanceof \SimpleXmlElement;
    }
}