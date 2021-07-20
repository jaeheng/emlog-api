<?php
/*
Plugin Name: 微信小程序辅助插件
Version: 1.0
Plugin URL:
Description: 1. 加密算法配置
Author: jaeheng
Author URL: https://blog.zhangziheng.com
*/
!defined('EMLOG_ROOT') && exit('access deined!');

//插件设置页面
function plugin_setting_view() {
    if (isset($_POST['salt'])) {
        $CACHE = Cache::getInstance();
        $code = $_POST['salt'];
        Option::updateOption('wxa_salt', $code);
        $CACHE->updateCache('options');
        $url = '?plugin=wxa';
        Header("HTTP/1.1 303 See Other");
        Header("Location: $url");
        exit;
    }
}

$code = Option::get('wxa_salt');
?>
<style>
.wxa h1 {
    font-size: 18px;
}
.wxa .code {
    background: #eee;
    padding: 10px;
}
.wxa .input {
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    width: 50%;
    padding: 5px;
}
</style>
<div class="wxa">
    <h1>微信小程序辅助插件</h1>
    <h3>接口地址</h3>
    <p class="code"><?php echo BLOG_URL;?>content/wxa/api.php</p>
    <h3>小程序导出的html样式地址</h3>
    <p>网页中要展示的时候，需要将该css引入</p>
    <p class="code"><?php echo BLOG_URL;?>content/wxa/style.css</p>
    <h3>加密字符串</h3>
    <p>将加密字符串复制到小程序的配置文件中，用于发布文章时鉴定权限</p>
    <p class="code"><?php echo md5(md5($code));?></p>

    <h3>混淆码配置</h3>
    <form action="?plugin=wxa" method="post">
        混淆码: <input type="text" name="salt" class="input" value="<?php echo $code;?>">
        <input type="submit" value="提交">
    </form>
</div>
