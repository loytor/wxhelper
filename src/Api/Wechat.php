<?phpnamespace Loytor\Wxhelper\Api;use Loytor\Wxhelper\Utils\Http;class Wechat extends ApiAbstract{    const URL_GETWXACODEUNLIMIT = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit';    const URL_WXOPEN_TEMPLATE_SEND = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send';    const URL_TICKET_GETTICKET = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';    const URL_WXA_COMMIT = 'https://api.weixin.qq.com/wxa/commit';    const URL_WXA_GET_CATEGORY = 'https://api.weixin.qq.com/wxa/get_category';    const URL_WXA_GET_PAGE = 'https://api.weixin.qq.com/wxa/get_page';    const URL_WXA_SUBMIT_AUDIT = 'https://api.weixin.qq.com/wxa/submit_audit';    const URL_WXA_GET_AUDITSTATUS = 'https://api.weixin.qq.com/wxa/get_auditstatus';    const URL_WXA_GET_LATEST_AUDITSTATUS = 'https://api.weixin.qq.com/wxa/get_latest_auditstatus';    const URL_WXA_RELEASE = 'https://api.weixin.qq.com/wxa/release';    const URL_WXA_CHANGE_VISITSTATUS = 'https://api.weixin.qq.com/wxa/change_visitstatus';    const URL_WXA_GET_QRCODE = 'https://api.weixin.qq.com/wxa/get_qrcode';    const URL_WXA_MODIFY_DOMAIN = 'https://api.weixin.qq.com/wxa/modify_domain';    const URL_MESSAGE_CUSTOM_SEND = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';    const URL_WXA_UNDOCODEAUDIT = 'https://api.weixin.qq.com/wxa/undocodeaudit';    const URL_WXA_SETWEBVIEWDOMAIN = 'https://api.weixin.qq.com/wxa/setwebviewdomain';    /**     * @param string $scene     * @param string $page     * @param int $width     * @param bool $auto_color     * @param array $line_color     * @return bool|mixed     */    public function getWxaCodeUnlimit(string $scene, string $page, int $width = 430, bool $auto_color = false, array $line_color = ['r' => 0, 'g' => 0, 'b' => 0])    {        $result = Http::request('POST', self::URL_GETWXACODEUNLIMIT)->withAuthorizerAccessToken($this->access_token)->withBody([            'scene' => $scene,            'page' => $page,            'width' => $width,            'auto_color' => $auto_color,            'line_color' => $line_color        ])->send();        if (isset($result['errcode'])) {            $this->setErrorMsg($result);            return null;        } else {            return $result;        }    }    public function wxopenTemplateSend(string $touser, string $template_id, string $page = '', string $form_id = '', array $data = [], string $emphasis_keyword = '')    {        $result = Http::request('POST', self::URL_WXOPEN_TEMPLATE_SEND)->withAuthorizerAccessToken($this->access_token)            #->withJson([            ->withBody([                'touser' => $touser,                'template_id' => $template_id,                'page' => $page ?: '',                'form_id' => $form_id,                'data' => $data ?: [],                'emphasis_keyword' => $emphasis_keyword ?: '',            ])            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    /**     *     * 获取公众平台JsApiTicket     *     * @param string $type     * @return bool     */    public function ticketGetticket($type = 'jsapi')//wx_card|jsapi    {        $result = Http::request('GET', self::URL_TICKET_GETTICKET)            ->withAuthorizerAccessToken($this->access_token)            ->withQuery(['type' => $type])            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    public function wxaCommit(Array $data)    {        $result = Http::request('POST', self::URL_WXA_COMMIT)            ->withAuthorizerAccessToken($this->access_token)            ->withBody($data)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    /**     * 小程序体验二维码     */    public function wxaGetQrcode()    {        $result = Http::request('GET', self::URL_WXA_GET_QRCODE)            ->withAuthorizerAccessToken($this->access_token)            ->send();        if (isset($result['errcode'])) {            $this->setErrorMsg($result);            return null;        } else {            return $result;        }    }    public function wxaGetCategory()    {        $result = Http::request('GET', self::URL_WXA_GET_CATEGORY)            ->withAuthorizerAccessToken($this->access_token)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    public function wxaGetPage()    {        $result = Http::request('GET', self::URL_WXA_GET_PAGE)            ->withAuthorizerAccessToken($this->access_token)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    /**     * 将第三方提交的代码包提交审核（仅供第三方开发者代小程序调用）     *     * {     * "item_list": [     * {     *  "address":"index",     *  "tag":"学习 生活",     *  "first_class": "文娱",     *  "second_class": "资讯",     *  "first_id":1,     *  "second_id":2,     *  "title": "首页"     * },     * {     *  "address":"page/logs/logs",     *  "tag":"学习 工作",     *  "first_class": "教育",     *  "second_class": "学历教育",     *  "third_class": "高等",     *  "first_id":3,     *  "second_id":4,     *  "third_id":5,     *  "title": "日志"     * }     * ]     * }     * @return     * {     *  "errcode":0,     *  "errmsg":"ok",     *  "auditid":1234567     * }     *     * @xml     * <xml><ToUserName><![CDATA[gh_fb9688c2a4b2]]></ToUserName>     * <FromUserName><![CDATA[od1P50M-fNQI5Gcq-trm4a7apsU8]]></FromUserName>     * <CreateTime>1488856741</CreateTime>     * <MsgType><![CDATA[event]]></MsgType>     * <Event><![CDATA[weapp_audit_success]]></Event>     * <SuccTime>1488856741</SuccTime>     * </xml>     */    public function wxaSubmitAudit(Array $data, $with_debug = false)    {        $result = Http::request('POST', self::URL_WXA_SUBMIT_AUDIT)            ->withAuthorizerAccessToken($this->access_token)            ->withBody($data)            ->withDebug($with_debug)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    /**     * 查询某个指定版本的审核状态（仅供第三方代小程序调用）     * @param $auditid     * @return array|null|string     */    public function wxaGetAuditstatus($auditid)    {        $result = Http::request('POST', self::URL_WXA_GET_AUDITSTATUS)            ->withAuthorizerAccessToken($this->access_token)            ->withBody(['auditid' => $auditid])            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    /**     * 查询最新一次提交的审核状态（仅供第三方代小程序调用）     *     * @return array|null|string     */    public function wxaGetLatestAuditstatus()    {        $result = Http::request('GET', self::URL_WXA_GET_LATEST_AUDITSTATUS)            ->withAuthorizerAccessToken($this->access_token)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    /**     * 9、发布已通过审核的小程序（仅供第三方代小程序调用）     *     * 请求方式: POST（请使用https协议）     *     * https://api.weixin.qq.com/wxa/release?access_token=TOKEN     *     * POST数据示例:     *     * { }     *     * 参数说明： 请填写空的数据包，POST的json数据包为空即可。     */    public function wxaRelease()    {        $result = Http::request('POST', self::URL_WXA_RELEASE)            ->withAuthorizerAccessToken($this->access_token)            ->withEmptyJson()            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    public function wxaModifyDomain($data)    {        $result = Http::request('POST', self::URL_WXA_MODIFY_DOMAIN)            ->withAuthorizerAccessToken($this->access_token)            ->withBody($data)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    public function messageCustomSend($data)    {        $result = Http::request('POST', self::URL_MESSAGE_CUSTOM_SEND)            ->withAuthorizerAccessToken($this->access_token)            ->withBody($data)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    public function wxaUndocodeaudit()    {        $result = Http::request('GET', self::URL_WXA_UNDOCODEAUDIT)            ->withAuthorizerAccessToken($this->access_token)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }    public function wxaSetwebviewdomain($data)    {        $result = Http::request('POST', self::URL_WXA_SETWEBVIEWDOMAIN)            ->withAuthorizerAccessToken($this->access_token)            ->withBody($data)            ->send();        if (isset($result['errcode']) && ($result['errcode'] != 0)) {            $this->setErrorMsg($result);            return null;        }        return $result;    }}