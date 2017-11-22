## 微信第三方开放平台类库
-- PHP7+

### -------------- 消息实体 --------------------------
        $entity = new Entity([
            'component_appid' => config('component.component_appid'),
            'component_key' => config('component.component_key'),
            'component_token' => config('component.component_token'),
        ]);
##### 回复文本消息
        if (Text::instance($entity)->isValid()) {
            return Reply::instance($entity)->text('你好');
        } else {
            return 'success';
        }
        
##### 回复多文本消息
        return Reply::instance($entity)->news([['title'=>'ccc','url'=>'dddd','description'=>'描述']]);
        
##### 验证消息类型
        if (Text::instance($entity)->isValid()) {
          //TODO ...
        }
        
### -------------- 微信接口调用 --------------------------        
##### 小程序换取 openid
        $userdata = Component::instance($component_access_token, $component_appid)->getWxappOpenidSessionKey($appid, $js_code);
        $openid = $userdata['openid'] ?? '';
        $session_key = $userdata['session_key'] ?? '';

*2017-11-22 文档完善中.....*
