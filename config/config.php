
<?php
// ==================== config/config.php - 主配置文件 ====================
// 全局配置文件
define('APP_NAME', 'CMS Website System');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', false); // 生产环境设为false

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告设置
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// 会话设置
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);

// 上传设置
define('UPLOAD_MAX_SIZE', 32 * 1024 * 1024); // 32MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('ALLOWED_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf', 'application/msword', 'application/vnd.ms-excel'
]);

// 缓存设置
define('CACHE_ENABLED', true);
define('CACHE_TIME', 3600); // 1小时
define('CACHE_PATH', __DIR__ . '/../cache/');

// 邮件设置
define('MAIL_FROM_EMAIL', 'noreply@example.com');
define('MAIL_FROM_NAME', 'CMS System');

// 安全设置
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300); // 5分钟

// 分页设置
define('DEFAULT_PER_PAGE', 20);
define('MAX_PER_PAGE', 100);

// API设置
define('API_RATE_LIMIT', 100); // 每小时请求次数限制
define('API_TOKEN_EXPIRE', 86400); // 24小时

// 创建必要的目录
$directories = [
    __DIR__ . '/../logs',
    __DIR__ . '/../cache',
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/system',
    __DIR__ . '/../uploads/articles',
    __DIR__ . '/../uploads/temp'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 自动加载函数
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});