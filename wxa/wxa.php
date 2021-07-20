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
function wxa_setting_sidebar() {
    echo '<div class="sidebarsubmenu" id="wxa-setting"><a href="./plugin.php?plugin=wxa">微信小程序辅助</a></div>';
}

addAction('adm_sidebar_ext', 'wxa_setting_sidebar');