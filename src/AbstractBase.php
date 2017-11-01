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
            'errmsg' => $this->last_errmsg ?: 'no-error'
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

    protected function _getData($url, $data)
    {
        $client = new Client();
        $result = $client->request('GET', $url, [
            'query' => $data
        ]);
        $result = $result->getBody()->getContents();
        return $this->_handelResult($result);
    }

    /**
     * @param $result
     * @return bool|mixed
     */
    protected function _handelResult($result)
    {
        $result = \GuzzleHttp\json_decode($result, true);
        if (isset($result['errcode'])) {
            $this->last_errcode = $result['errcode'];
            if (isset($result['errmsg'])) {
                $this->last_errmsg = $result['errmsg'];
            }
            return false;
        }
        return $result;
    }
}