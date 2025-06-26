<?php
// admin/logout.php - 后台退出登录处理

// 引入核心配置文件，确保 ABSPATH 和其他常量被定义
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php'; 

// 引入 Auth 类和 functions.php
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php'; // Auth 类依赖 Database
require_once ABSPATH . 'includes/Auth.php'; 

// 获取 Auth 类的实例（单例模式）
// 修复点：不能直接 new Auth()，必须通过 getInstance()
$auth = Auth::getInstance(); 

// 调用 Auth 类的 logout 方法
$auth->logout();

// 设置一个成功消息，用于重定向到登录页后显示
set_flash_message('您已成功退出登录。', 'success');

// 重定向到登录页面
safe_redirect(SITE_URL . '/admin/login.php'); 
exit; // 确保脚本在此处终止