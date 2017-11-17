<?php
/**
 * Created by PhpStorm.
 * User: yanbingti
 * Date: 2017/11/16
 * Time: 下午3:47
 */

namespace Loytor\Wxhelper\Api;


trait ApiTrait
{
    protected $access_token;
    protected $appid;

    public function __construct($access_token, $appid)
    {
        $this->access_token = $access_token;
        $this->appid = $appid;
    }

    public static function instance($access_token, $appid)
    {
        return new static($access_token, $appid);
    }
}