<?php

namespace Loytor\Wxhelper\Utils;

class Serializer
{
    public static function array2Xml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
        $xml .= "</xml>";
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