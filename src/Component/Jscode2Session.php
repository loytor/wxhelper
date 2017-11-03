<?phpnamespace Loytor\Wxhelper\Component;use Loytor\Wxhelper\AbstractComponent;class Jscode2Session extends AbstractComponent{    const REQUEST_BASE = "https://api.weixin.qq.com/sns/component/jscode2session";    /**     * 设置授权方的选项信息     */    public function execute($authorizer_appid = "", $js_code = "")    {        $result = $this->_postData($this->getRequestUrl(self::REQUEST_BASE), [            'appid' => $authorizer_appid,            'component_appid' => $this->getComponentId(),            'js_code' => $js_code,            'grant_type' => 'authorization_code',            'component_access_token' => $this->getComponentAccessToken(),        ]);        if (isset($result['openid']) && isset($result['session_key'])) {            return $result;        } else {            return false;        }    }}