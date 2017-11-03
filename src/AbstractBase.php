<?php

namespace Loytor\Wxhelper;

use GuzzleHttp\Client;

abstract class AbstractBase
{
    protected $last_errcode;
    protected $last_errmsg;

    public function getLastError()
    {
        return [
            'errcode' => $this->last_errcode ?: 0,
            'errmsg' => $this->last_errmsg ?: 'no-define'
        ];
    }

    protected function _postData($url, $data)
    {
        $client = new Client;
        $result = $client->request('POST', $url, [
            'json' => $data
        ]);
        $result = $result->getBody()->getContents();
        return $this->_handelResult($result);
    }

    protected function _postXmlData($url, $data)
    {
        $client = new Client;
        $respond = $client->request('POST', $url, [
            'headers' => [
                'content-type' => 'text/plain',
            ],
            'body' => $this->_array2Xml($data),
            'debug' => true
        ]);
        $result = $respond->getBody()->getContents();
        $result = $this->_xml2Array($result);
        return $this->_handelResult($result);
    }

    protected function _getData($url, $data)
    {
        $client = new Client();
        $result = $client->request('GET', $url, [
            'query' => $data
        ]);
        $result = $result->getBody()->getContents();
        return $this->_handelResult($result);
    }

    protected function _getQrcode($url, $data)
    {
        $client = new Client;
        $respond = $client->request('POST', $url, [
            'json' => $data
        ]);
        $result = $respond->getBody()->getContents();
        $contentType = $respond->getHeaderLine('content-type');#["image/jpeg"]#如果图片类型返回图片数据

        if (preg_match('/^application\/json/', $contentType)) {
            return $this->_handelResult($result);
        }
        return $result;
    }

    /**
     * @param $result
     * @return bool|mixed
     */
    protected function _handelResult($result)
    {
        if (!is_array($result)) {
            $result = \GuzzleHttp\json_decode($result, true);
        }
        if (isset($result['errcode'])) {
            $this->last_errcode = $result['errcode'];
            if (isset($result['errmsg'])) {
                $this->last_errmsg = $result['errmsg'];
            }
            return false;
        }
        return $result;
    }

    protected function _array2Xml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    protected function _xml2Array($xml = "")
    {
        $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $resp = json_decode(json_encode($xml), TRUE);
        return $resp;
    }
}