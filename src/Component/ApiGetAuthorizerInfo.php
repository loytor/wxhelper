<?phpnamespace Loytor\Wxhelper\Component;use Loytor\Wxhelper\AbstractComponent;class ApiGetAuthorizerInfo extends AbstractComponent{    const REQUEST_BASE = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info";    public function execute($authorizer_appid)    {        $result = $this->_postData($this->getRequestUrl(self::REQUEST_BASE), [            'component_appid' => $this->getComponentId(),            'authorizer_appid' => $authorizer_appid,        ]);        if (isset($result['errcode'])) {            return false;        } else {            return $result;        }    }}