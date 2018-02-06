<?php

namespace Loytor\Wxhelper\Utils;

use GuzzleHttp\Client;

class Http
{
    protected $method;
    protected $uri;
    protected $body;
    protected $json;
    protected $query;
    protected $debug;
    protected $headers;
    protected $ssl_cert;
    protected $ssl_key;

    public function __construct($method, $uri)
    {
        $this->uri = $uri;
        $this->method = strtoupper($method);
    }

    public static function request($method, $uri)
    {
        return new static($method, $uri);
    }

    public function withDebug(bool $boolen = false)
    {
        $this->debug = $boolen;
        return $this;
    }

    public function withHeader(string $key, string $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withQuery(array $query)
    {
        if ($this->query) {
            $this->query = array_merge($this->query, $query);
        } else {
            $this->query = $query;
        }
        return $this;
    }

    public function withBody(array $body)
    {
        $this->body = Serializer::array2Json($body);
        return $this;
    }

    public function withJson(string $body)
    {
        $this->json = $body;
        return $this;
    }

    public function withXmlBody(array $body)
    {
        $this->body = Serializer::array2Xml($body);
        return $this;
    }

    public function withAuthorizerAccessToken($access_token)
    {
        $this->query['access_token'] = $access_token;
        return $this;
    }

    public function withComponentAccessToken($component_access_token)
    {
        $this->query['component_access_token'] = $component_access_token;
        return $this;
    }

    public function withSSLCert($ssl_cert, $ssl_key)
    {
        $this->ssl_cert = $ssl_cert;
        $this->ssl_key = $ssl_key;

        return $this;
    }

    public function send()
    {
        $options = $this->getOptions();
        $response = (new Client())->request($this->method, $this->uri, $options ?: []);
        $content_type = $response->getHeaderLine('content-type');
        $contents = $response->getBody()->getContents();
        if (preg_match('/^image/', $content_type)) {#如果是个图片，直接返回图片，否则转成数组
            return $contents;
        }

        return Serializer::parse($contents);
    }

    public function getOptions()
    {
        $this->query ? $options['query'] = $this->query : true;
        $this->body ? $options['body'] = $this->body : true;
        $this->json ? $options['json'] = $this->json : true;
        $this->ssl_cert ? $options = array_merge($options ?? [], ['cert' => $this->ssl_cert, 'ssl_key' => $this->ssl_key]) : true;
        $this->debug ? $options['debug'] = true : true;
        $this->headers ? $options['headers'] = $this->headers : true;
        return $options ?? [];
    }
}