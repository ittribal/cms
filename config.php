<?php
/**
 * CMS系统核心配置文件
 * 包含数据库配置、系统设置、安全配置等
 * 
 * @version 1.0
 * @author CMS Team
 * @created 2024
 */

// 防止直接访问
if (!defined('CMS_LOADED')) {
    define('CMS_LOADED', true);
}

// ==================== 基础路径配置 ====================
define('ROOT_PATH', dirname(__FILE__));
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');

// URL路径配置
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$base_url = $protocol . $host . rtrim(dirname($script_name), '/\\') . '/';

define('BASE_URL', $base_url);
define('ADMIN_URL', BASE_URL . 'admin/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('ASSETS_URL', BASE_URL . 'assets/');

// ==================== 数据库配置 ====================
define('DB_HOST', 'localhost');
define('DB_NAME', '10_10_10_99_1100');
define('DB_USER', '10_10_10_99_1100');
define('DB_PASS', 'ASPJmr2srFt1C4nE');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', '');

// 数据库连接选项
$db_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_TIMEOUT => 30
];

// ==================== 系统基础配置 ====================
define('SITE_NAME', '网站技术指南CMS');
define('SITE_DESCRIPTION', '专业的网站技术学习和内容管理平台');
define('SITE_KEYWORDS', 'CMS,PHP,MySQL,HTML5,CSS3,JavaScript,网站开发');
define('SITE_AUTHOR', 'CMS Team');
define('SITE_VERSION', '1.0.0');

// 系统状态
define('MAINTENANCE_MODE', false);
define('DEBUG_MODE', false);
define('ENABLE_CACHE', true);
define('ENABLE_LOGS', true);

// ==================== 安全配置 ====================
// 密码安全
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', false);

// Session配置
define('SESSION_LIFETIME', 7200); // 2小时
define('SESSION_NAME', 'CMS_SESSION');
define('SESSION_SECURE', false); // HTTPS环境下设为true
define('SESSION_HTTPONLY', true);

// CSRF防护
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600);

// 登录安全
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 1800); // 30分钟
define('ENABLE_REMEMBER_ME', true);
define('REMEMBER_ME_LIFETIME', 2592000); // 30天

// IP白名单(可选,空数组表示不限制)
$allowed_ips = [
    // '127.0.0.1',
    // '192.168.1.*',
];

// ==================== 文件上传配置 ====================
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', [
    'image/jpeg',
    'image/png', 
    'image/gif',
    'image/webp',
    'application/pdf',
    'text/plain',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

define('IMAGE_MAX_WIDTH', 1920);
define('IMAGE_MAX_HEIGHT', 1080);
define('IMAGE_QUALITY', 85);

// 文件存储配置
define('STORAGE_TYPE', 'local'); // local, ftp, cloud
define('AUTO_GENERATE_THUMBNAILS', true);
define('THUMBNAIL_SIZES', [
    'small' => [150, 150],
    'medium' => [300, 300],
    'large' => [800, 600]
]);

// ==================== 邮件配置 ====================
define('MAIL_ENABLED', true);
define('MAIL_METHOD', 'smtp'); // smtp, mail, sendmail

// SMTP配置
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // ssl, tls
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// 邮件默认设置
define('MAIL_FROM_EMAIL', 'noreply@yoursite.com');
define('MAIL_FROM_NAME', SITE_NAME);
define('MAIL_CHARSET', 'UTF-8');

// ==================== 缓存配置 ====================
define('CACHE_ENABLED', true);
define('CACHE_TYPE', 'file'); // file, memcache, redis
define('CACHE_LIFETIME', 3600); // 1小时
define('CACHE_PREFIX', 'cms_');

// 页面缓存
define('PAGE_CACHE_ENABLED', true);
define('PAGE_CACHE_LIFETIME', 1800); // 30分钟

// 数据缓存
define('DATA_CACHE_ENABLED', true);
define('DATA_CACHE_LIFETIME', 900); // 15分钟

// ==================== 分页配置 ====================
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);
define('PAGINATION_RANGE', 5);

// ==================== SEO配置 ====================
define('SEO_ENABLED', true);
define('FRIENDLY_URLS', true);
define('AUTO_GENERATE_SITEMAP', true);
define('SITEMAP_UPDATE_FREQUENCY', 'daily');

// Meta标签默认值
define('DEFAULT_META_TITLE', SITE_NAME);
define('DEFAULT_META_DESCRIPTION', SITE_DESCRIPTION);
define('DEFAULT_META_KEYWORDS', SITE_KEYWORDS);

// ==================== 内容配置 ====================
define('ALLOW_HTML_IN_CONTENT', true);
define('CONTENT_MAX_LENGTH', 100000);
define('EXCERPT_LENGTH', 300);
define('AUTO_GENERATE_EXCERPT', true);

// 富文本编辑器配置
define('EDITOR_TYPE', 'tinymce'); // tinymce, ckeditor, markdown
define('EDITOR_PLUGINS', [
    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
    'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
    'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount'
]);

// ==================== 用户权限配置 ====================
$user_roles = [
    1 => [
        'name' => '超级管理员',
        'permissions' => ['*'] // 所有权限
    ],
    2 => [
        'name' => '管理员',
        'permissions' => [
            'user.view', 'user.create', 'user.edit',
            'content.view', 'content.create', 'content.edit', 'content.delete',
            'category.view', 'category.create', 'category.edit',
            'media.view', 'media.upload', 'media.delete',
            'setting.view'
        ]
    ],
    3 => [
        'name' => '编辑',
        'permissions' => [
            'content.view', 'content.create', 'content.edit',
            'category.view',
            'media.view', 'media.upload'
        ]
    ],
    4 => [
        'name' => '作者',
        'permissions' => [
            'content.view', 'content.create', 'content.edit_own',
            'media.view', 'media.upload'
        ]
    ],
    5 => [
        'name' => '订阅者',
        'permissions' => [
            'content.view'
        ]
    ]
];

define('USER_ROLES', serialize($user_roles));

// ==================== API配置 ====================
define('API_ENABLED', true);
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 1000); // 每小时请求次数
define('API_KEY_REQUIRED', false);
define('API_JWT_SECRET', 'your-jwt-secret-key-change-this');
define('API_JWT_LIFETIME', 3600);

// ==================== 主题和模板配置 ====================
define('CURRENT_THEME', 'default');
define('TEMPLATE_CACHE_ENABLED', true);
define('TEMPLATE_CACHE_LIFETIME', 3600);
define('MINIFY_HTML', false);
define('MINIFY_CSS', false);
define('MINIFY_JS', false);

// ==================== 日志配置 ====================
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('LOG_MAX_FILES', 30);
define('LOG_FORMAT', '[%datetime%] %level_name%: %message% %context%');

// 记录的事件类型
$log_events = [
    'user_login',
    'user_logout', 
    'user_created',
    'user_updated',
    'user_deleted',
    'content_created',
    'content_updated',
    'content_deleted',
    'settings_updated',
    'file_uploaded',
    'error_occurred'
];

define('LOG_EVENTS', serialize($log_events));

// ==================== 第三方服务配置 ====================
// 图片处理服务
define('IMAGE_SERVICE', 'gd'); // gd, imagemagick
define('ENABLE_WEBP_CONVERSION', true);

// CDN配置
define('CDN_ENABLED', false);
define('CDN_URL', 'https://cdn.yoursite.com/');
define('CDN_TYPES', ['js', 'css', 'images']);

// 社交分享
define('SOCIAL_SHARE_ENABLED', true);
$social_platforms = [
    'facebook' => true,
    'twitter' => true,
    'linkedin' => true,
    'weibo' => true,
    'wechat' => true,
    'qq' => true
];
define('SOCIAL_PLATFORMS', serialize($social_platforms));

// ==================== 数据库表配置 ====================
$db_tables = [
    'admin_users' => DB_PREFIX . 'admin_users',
    'admin_roles' => DB_PREFIX . 'admin_roles',
    'articles' => DB_PREFIX . 'articles',
    'categories' => DB_PREFIX . 'categories',
    'media_files' => DB_PREFIX . 'media_files',
    'site_settings' => DB_PREFIX . 'site_settings',
    'admin_logs' => DB_PREFIX . 'admin_logs',
    'user_sessions' => DB_PREFIX . 'user_sessions',
    'login_attempts' => DB_PREFIX . 'login_attempts'
];

define('DB_TABLES', serialize($db_tables));

// ==================== 错误处理配置 ====================
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
}

// ==================== 时区和本地化配置 ====================
define('DEFAULT_TIMEZONE', 'Asia/Shanghai');
define('DEFAULT_LANGUAGE', 'zh-CN');
define('DEFAULT_CHARSET', 'UTF-8');
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// 设置时区
date_default_timezone_set(DEFAULT_TIMEZONE);

// ==================== 自动加载配置 ====================
spl_autoload_register(function ($class_name) {
    $directories = [
        INCLUDES_PATH . '/classes/',
        INCLUDES_PATH . '/models/',
        INCLUDES_PATH . '/controllers/',
        INCLUDES_PATH . '/helpers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});

// ==================== 性能优化配置 ====================
// 压缩输出
if (!DEBUG_MODE) {
    if (extension_loaded('zlib') && !ob_get_level()) {
        ob_start('ob_gzhandler');
    }
}

// 设置内存限制
ini_set('memory_limit', '256M');

// 设置执行时间限制
set_time_limit(60);

// ==================== 安全标头配置 ====================
if (!headers_sent()) {
    // 防止点击劫持
    header('X-Frame-Options: SAMEORIGIN');
    
    // 防止MIME类型嗅探
    header('X-Content-Type-Options: nosniff');
    
    // XSS保护
    header('X-XSS-Protection: 1; mode=block');
    
    // 引用来源策略
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // 内容安全策略(可根据需要调整)
    if (!DEBUG_MODE) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:;");
    }
}

// ==================== 环境检查 ====================
function checkSystemRequirements() {
    $requirements = [
        'PHP版本' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO扩展' => extension_loaded('pdo'),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'mbstring扩展' => extension_loaded('mbstring'),
        'GD扩展' => extension_loaded('gd'),
        'fileinfo扩展' => extension_loaded('fileinfo'),
        'JSON扩展' => extension_loaded('json'),
        'Session支持' => function_exists('session_start'),
        '上传目录可写' => is_writable(UPLOADS_PATH),
        '缓存目录可写' => is_writable(CACHE_PATH),
        '日志目录可写' => is_writable(LOGS_PATH)
    ];
    
    $errors = [];
    foreach ($requirements as $name => $check) {
        if (!$check) {
            $errors[] = $name;
        }
    }
    
    return $errors;
}

// ==================== 初始化设置 ====================
// 创建必要的目录
$directories = [UPLOADS_PATH, CACHE_PATH, LOGS_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 创建.htaccess文件保护敏感目录
$protected_dirs = [
    CACHE_PATH => "Options -Indexes\nDeny from all",
    LOGS_PATH => "Options -Indexes\nDeny from all",
    INCLUDES_PATH => "Options -Indexes\nDeny from all"
];

foreach ($protected_dirs as $dir => $content) {
    $htaccess_file = $dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        file_put_contents($htaccess_file, $content);
    }
}

// ==================== 常用工具函数 ====================
/**
 * 获取配置值
 */
function config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * 安全获取$_POST数据
 */
function post($key, $default = null, $filter = FILTER_SANITIZE_STRING) {
    return isset($_POST[$key]) ? filter_var($_POST[$key], $filter) : $default;
}

/**
 * 安全获取$_GET数据  
 */
function get($key, $default = null, $filter = FILTER_SANITIZE_STRING) {
    return isset($_GET[$key]) ? filter_var($_GET[$key], $filter) : $default;
}

/**
 * 重定向
 */
function redirect($url, $code = 302) {
    if (!headers_sent()) {
        header("Location: $url", true, $code);
        exit;
    }
}

/**
 * 获取当前URL
 */
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * 格式化字节大小
 */
function format_bytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unit = 0;
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    return round($size, $precision) . ' ' . $units[$unit];
}

/**
 * 生成随机字符串
 */
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 验证邮箱格式
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 验证URL格式
 */
function is_valid_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// ==================== 结束配置 ====================
// 标记配置已加载
define('CONFIG_LOADED', true);

// 如果开启调试模式，显示配置信息
if (DEBUG_MODE && isset($_GET['debug_config'])) {
    echo "<pre>";
    echo "=== CMS系统配置信息 ===\n";
    echo "PHP版本: " . PHP_VERSION . "\n";
    echo "网站根目录: " . ROOT_PATH . "\n";
    echo "网站URL: " . BASE_URL . "\n";
    echo "数据库: " . DB_HOST . "/" . DB_NAME . "\n";
    echo "调试模式: " . (DEBUG_MODE ? '开启' : '关闭') . "\n";
    echo "缓存状态: " . (CACHE_ENABLED ? '开启' : '关闭') . "\n";
    echo "上传限制: " . format_bytes(UPLOAD_MAX_SIZE) . "\n";
    
    $errors = checkSystemRequirements();
    if (!empty($errors)) {
        echo "\n=== 系统检查错误 ===\n";
        foreach ($errors as $error) {
            echo "❌ " . $error . "\n";
        }
    } else {
        echo "\n✅ 系统检查通过\n";
    }
    echo "</pre>";
    exit;
}

?>