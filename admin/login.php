<?php
// admin/login.php - 后台登录页面

// 引入核心配置文件，确保 ABSPATH 和其他常量被定义
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php'; 

// 引入 Auth 类和 functions.php，因为 login 逻辑和辅助函数会用到
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php'; 
require_once ABSPATH . 'includes/Auth.php'; 

$auth = Auth::getInstance(); // 获取 Auth 类的实例
$error = ''; // 存储登录错误信息

// 如果用户已经登录，直接重定向到后台仪表盘
if ($auth->isLoggedIn()) {
    safe_redirect(SITE_URL . '/admin/system/dashboard.php'); // 使用安全重定向函数
}

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = sanitize_input($_POST['username']); // 清理用户名输入
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']); // 检查是否勾选“记住我”

    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        // 调用 Auth 类的 login 方法进行认证
        if ($auth->login($username, $password, $remember_me)) {
            // 登录成功，重定向到后台仪表盘
            safe_redirect(SITE_URL . '/admin/system/dashboard.php'); 
        } else {
            // 登录失败
            $error = '用户名或密码错误，请重试。';
        }
    }
}

// 获取一次性消息 (例如从登出页面跳转过来)
$flash_message = get_flash_message();
if ($flash_message) {
    // 将 flash message 转换为本地 $error 或 $message 用于显示
    if ($flash_message['type'] === 'error') {
        $error = $flash_message['message'];
    } else {
        $message = $flash_message['message']; // 可以显示成功消息
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - <?php echo esc_html(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/public/assets/favicon.ico">
    <style>
        /* login.php 特定样式，直接嵌入，或后续移入 admin/assets/css/login.css */
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left; /* 确保label和input左对齐 */
        }
        
        .form-group label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box; /* 确保内边距和边框不增加宽度 */
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
        }
        
        /* 统一的 alert 样式，这里可以直接复制 admin.css 的 alert 样式 */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #27ae60;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #e74c3c;
        }
        .alert i {
            font-size: 1.1rem;
        }

        .login-footer {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 12px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: #555;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            accent-color: #667eea; /* 勾选时的颜色 */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 管理员登录</h1>
            <p>请输入您的登录凭据</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo esc_html($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div class="form-group">
                <label for="username">用户名 / 邮箱</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo esc_attr($_POST['username'] ?? ''); ?>"
                       placeholder="请输入用户名或邮箱">
            </div>

            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required 
                       placeholder="请输入密码">
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">记住我</label>
            </div>

            <button type="submit" class="login-btn">登录</button>
        </form>

        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(SITE_TITLE); ?>. 保留所有权利.</p>
        </div>
    </div>
</body>
</html>