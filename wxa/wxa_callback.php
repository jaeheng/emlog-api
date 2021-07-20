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

//插件激活回调函数
function callback_init() {
    $options = Option::get('wxa_salt');
    if (!$options) {
        $db = Database::getInstance();
        $CACHE = Cache::getInstance();
        $str = md5(time());
        $sql = 'insert into ' . DB_PREFIX . 'options (option_name, option_value) values ("wxa_salt", "' . $str . '")';
        $db->query($sql);
        $CACHE->updateCache('options');
    }
}
