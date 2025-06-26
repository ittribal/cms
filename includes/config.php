<?php
// includes/config.php - 核心系统配置文件
// 防止直接访问
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/'); // 这行定义了 ABSPATH
}

// =============================================================
// 将 DEBUG_MODE 和其他在文件顶部使用的常量定义提前到这里
// =============================================================
define('DEBUG_MODE', true); // 提前定义 DEBUG_MODE
define('LOG_QUERIES', false); // 提前定义 LOG_QUERIES

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', DEBUG_MODE ? '1' : '0'); // 现在 DEBUG_MODE 已经被定义了
ini_set('log_errors', 1);
ini_set('error_log', ABSPATH . 'logs/error.log');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', '10_10_10_99_1100'); 
define('DB_USER', '10_10_10_99_1100'); // <--- 请务必核对这里的值是否与你宝塔面板中数据库的“用户名”完全一致
define('DB_PASS', 'ASPJmr2srFt1C4nE'); // <--- 请务必核对这里的值是否与你宝塔面板中数据库的“密码”完全一致

// 站点配置
define('SITE_URL', 'http://10.10.10.99:1100'); // <--- 请核对这里的值是否与你实际访问你网站的地址（包括 http/https 和端口）完全一致
define('SITE_TITLE', 'CMS管理系统');
define('SITE_DESCRIPTION', '基于PHP开发的内容管理系统');

// 安全配置
define('AUTH_KEY', 'your-unique-auth-key-here-for-hashes');
define('SECURE_AUTH_KEY', 'your-unique-secure-auth-key-here-for-secure-hashes');
define('LOGGED_IN_KEY', 'your-unique-logged-in-key-here-for-sessions');
define('NONCE_KEY', 'your-unique-nonce-key-here-for-nonces');

// 文件上传配置
define('UPLOAD_PATH', ABSPATH . 'uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx']); // 统一允许的文件类型

// 分页配置
define('POSTS_PER_PAGE', 10); // 前台每页文章数
define('ADMIN_ITEMS_PER_PAGE', 20); // 后台通用每页条目数

// 缓存配置
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1小时 (秒)
define('CACHE_PATH', ABSPATH . 'cache/');

// 邮件配置 - 示例，实际使用PHPMailer时可根据EmailService类配置
define('MAIL_FROM_NAME', SITE_TITLE);
define('MAIL_FROM_EMAIL', 'noreply@example.com');

// 自动加载类文件
spl_autoload_register(function ($class) {
    // 假设所有核心类都在 includes/ 目录下
    $file = ABSPATH . 'includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 确保必要的目录存在 (由 functions.php 中的 ensure_directory_exists 处理，但这里可用于核心目录)
// @ 符号会抑制警告，如果目录已经存在或有权限问题，避免报错
if (!is_dir(ABSPATH . 'logs')) @mkdir(ABSPATH . 'logs', 0755, true); 
if (!is_dir(ABSPATH . 'uploads')) @mkdir(ABSPATH . 'uploads', 0755, true);
if (!is_dir(ABSPATH . 'cache')) @mkdir(ABSPATH . 'cache', 0755, true);


// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(86400, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true); // 1天有效期, httponly, secure
    session_start();
}

// 安全头设置 - 确保在任何内容输出之前发送
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    // 仅在HTTPS下发送HSTS头
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}