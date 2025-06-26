<?php
/**
 * CMS数据库初始化脚本
 * 创建数据库、表结构和默认管理员账号
 * 使用完毕后请删除此文件
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否已经初始化过
$lock_file = 'init.lock';
if (file_exists($lock_file)) {
    die('❌ 数据库已经初始化过了！如需重新初始化，请先删除 ' . $lock_file . ' 文件。');
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS数据库初始化</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .success {
            color: #27ae60;
            background: #d5f4e6;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #e74c3c;
            background: #fdf2f2;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            color: #f39c12;
            background: #fef9e7;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #3498db;
            background: #ebf3fd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #229954;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .progress {
            background: #ecf0f1;
            border-radius: 10px;
            padding: 3px;
            margin: 10px 0;
        }
        .progress-bar {
            background: #3498db;
            height: 20px;
            border-radius: 7px;
            transition: width 0.3s ease;
        }
        .step {
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .step.completed {
            border-color: #27ae60;
            background: #f8fff9;
        }
        .step.error {
            border-color: #e74c3c;
            background: #fdf2f2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f8f9fa;
        }
        .code {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 CMS数据库初始化工具</h1>
        <div class="warning">
            <strong>⚠️ 重要提醒：</strong> 此脚本将创建或重置数据库！请确保您了解操作的后果。
        </div>

<?php
// 处理表单提交
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'init') {
        // 获取表单数据
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? 'cms_website';
        $db_user = $_POST['db_user'] ?? 'root';
        $db_pass = $_POST['db_pass'] ?? '';
        $admin_username = $_POST['admin_username'] ?? 'admin';
        $admin_email = $_POST['admin_email'] ?? 'admin@example.com';
        $admin_password = $_POST['admin_password'] ?? '123456';
        $create_sample_data = isset($_POST['create_sample_data']);
        
        echo "<h2>📋 初始化进度</h2>";
        
        $steps = [
            'connect' => '连接数据库服务器',
            'create_db' => '创建数据库',
            'create_tables' => '创建数据表',
            'create_admin' => '创建管理员账号',
            'create_roles' => '创建角色数据',
            'sample_data' => '创建示例数据',
            'config_file' => '生成配置文件'
        ];
        
        $completed_steps = [];
        $errors = [];
        
        try {
            // 步骤1：连接数据库服务器
            echo "<div class='step' id='step-connect'>";
            echo "<h3>📡 {$steps['connect']}</h3>";
            
            $pdo = new PDO(
                "mysql:host={$db_host};charset=utf8mb4",
                $db_user,
                $db_pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            echo "<div class='success'>✅ 数据库服务器连接成功</div>";
            $completed_steps[] = 'connect';
            echo "</div>";
            
            // 步骤2：创建数据库
            echo "<div class='step' id='step-create_db'>";
            echo "<h3>🗄️ {$steps['create_db']}</h3>";
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db_name}`");
            
            echo "<div class='success'>✅ 数据库 '{$db_name}' 创建成功</div>";
            $completed_steps[] = 'create_db';
            echo "</div>";
            
            // 步骤3：创建数据表
            echo "<div class='step' id='step-create_tables'>";
            echo "<h3>📋 {$steps['create_tables']}</h3>";
            
            // 创建users表
            $users_table = "
            CREATE TABLE IF NOT EXISTS `users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `email` varchar(100) NOT NULL,
                `password` varchar(255) NOT NULL,
                `role` enum('super_admin','admin','editor','author','subscriber') DEFAULT 'subscriber',
                `status` enum('active','inactive') DEFAULT 'active',
                `avatar` varchar(255) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `last_login` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`),
                UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($users_table);
            echo "<div class='success'>✅ users表创建成功</div>";
            
            // 创建roles表
            $roles_table = "
            CREATE TABLE IF NOT EXISTS `roles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                `slug` varchar(50) NOT NULL,
                `permissions` json DEFAULT NULL,
                `description` text,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($roles_table);
            echo "<div class='success'>✅ roles表创建成功</div>";
            
            // 创建categories表
            $categories_table = "
            CREATE TABLE IF NOT EXISTS `categories` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `slug` varchar(100) NOT NULL,
                `description` text,
                `parent_id` int(11) DEFAULT NULL,
                `sort_order` int(11) DEFAULT 0,
                `status` enum('active','inactive') DEFAULT 'active',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug` (`slug`),
                KEY `parent_id` (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($categories_table);
            echo "<div class='success'>✅ categories表创建成功</div>";
            
            // 创建articles表
            $articles_table = "
            CREATE TABLE IF NOT EXISTS `articles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `slug` varchar(255) NOT NULL,
                `content` longtext NOT NULL,
                `excerpt` text,
                `author_id` int(11) NOT NULL,
                `category_id` int(11) DEFAULT NULL,
                `featured_image` varchar(255) DEFAULT NULL,
                `status` enum('draft','published','pending','archived') DEFAULT 'draft',
                `meta_title` varchar(255) DEFAULT NULL,
                `meta_description` text,
                `views` int(11) DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `published_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug` (`slug`),
                KEY `author_id` (`author_id`),
                KEY `category_id` (`category_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($articles_table);
            echo "<div class='success'>✅ articles表创建成功</div>";
            
            // 创建admin_logs表
            $admin_logs_table = "
            CREATE TABLE IF NOT EXISTS `admin_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) DEFAULT NULL,
                `action` varchar(100) NOT NULL,
                `table_name` varchar(50) DEFAULT NULL,
                `record_id` int(11) DEFAULT NULL,
                `old_values` json DEFAULT NULL,
                `new_values` json DEFAULT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `action` (`action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($admin_logs_table);
            echo "<div class='success'>✅ admin_logs表创建成功</div>";
            
            // 创建settings表
            $settings_table = "
            CREATE TABLE IF NOT EXISTS `settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(100) NOT NULL,
                `setting_value` text,
                `description` varchar(255) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($settings_table);
            echo "<div class='success'>✅ settings表创建成功</div>";
            
            // 创建login_attempts表
            $login_attempts_table = "
            CREATE TABLE IF NOT EXISTS `login_attempts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `ip_address` varchar(45) NOT NULL,
                `reason` varchar(100) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `ip_address` (`ip_address`),
                KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($login_attempts_table);
            echo "<div class='success'>✅ login_attempts表创建成功</div>";
            
            $completed_steps[] = 'create_tables';
            echo "</div>";
            
            // 步骤4：创建角色数据
            echo "<div class='step' id='step-create_roles'>";
            echo "<h3>👥 {$steps['create_roles']}</h3>";
            
            $roles_data = [
                ['超级管理员', 'super_admin', '["*"]', '拥有系统所有权限'],
                ['管理员', 'admin', '["user.view","user.edit","article.view","article.edit","category.view","category.edit"]', '拥有大部分管理权限'],
                ['编辑', 'editor', '["article.view","article.edit","category.view"]', '可以管理文章和分类'],
                ['作者', 'author', '["article.view","article.create","article.edit_own"]', '可以创建和编辑自己的文章'],
                ['订阅者', 'subscriber', '["article.view"]', '只能查看已发布的文章']
            ];
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO roles (name, slug, permissions, description) VALUES (?, ?, ?, ?)");
            foreach ($roles_data as $role) {
                $stmt->execute($role);
            }
            
            echo "<div class='success'>✅ 角色数据创建成功</div>";
            $completed_steps[] = 'create_roles';
            echo "</div>";
            
            // 步骤5：创建管理员账号
            echo "<div class='step' id='step-create_admin'>";
            echo "<h3>👤 {$steps['create_admin']}</h3>";
            
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'super_admin', 'active')");
            $result = $stmt->execute([$admin_username, $admin_email, $hashed_password]);
            
            if ($result) {
                echo "<div class='success'>✅ 管理员账号创建成功</div>";
                echo "<div class='info'>";
                echo "<strong>管理员信息：</strong><br>";
                echo "用户名: {$admin_username}<br>";
                echo "邮箱: {$admin_email}<br>";
                echo "密码: {$admin_password}<br>";
                echo "</div>";
            } else {
                echo "<div class='warning'>⚠️ 管理员账号可能已存在</div>";
            }
            
            $completed_steps[] = 'create_admin';
            echo "</div>";
            
            // 步骤6：创建示例数据
            if ($create_sample_data) {
                echo "<div class='step' id='step-sample_data'>";
                echo "<h3>📝 {$steps['sample_data']}</h3>";
                
                // 创建示例分类
                $categories = [
                    ['技术分享', 'tech', '分享各种技术文章和教程'],
                    ['生活随笔', 'life', '记录生活中的点点滴滴'],
                    ['学习笔记', 'study', '学习过程中的心得体会']
                ];
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
                foreach ($categories as $category) {
                    $stmt->execute($category);
                }
                echo "<div class='success'>✅ 示例分类创建成功</div>";
                
                // 创建示例文章
                $admin_id = $pdo->lastInsertId() ?: 1;
                $category_id = $pdo->query("SELECT id FROM categories LIMIT 1")->fetchColumn();
                
                $articles = [
                    [
                        '欢迎使用CMS系统',
                        'welcome-to-cms',
                        '<h2>欢迎使用我们的CMS系统！</h2><p>这是一个功能强大、易于使用的内容管理系统。</p><p>主要特性包括：</p><ul><li>用户权限管理</li><li>文章发布系统</li><li>分类管理</li><li>SEO优化</li><li>响应式设计</li></ul>',
                        '欢迎使用我们的CMS系统！这里介绍了系统的主要特性和使用方法。',
                        $admin_id,
                        $category_id,
                        'published'
                    ],
                    [
                        '如何开始使用',
                        'how-to-get-started',
                        '<h2>快速开始指南</h2><p>按照以下步骤开始使用系统：</p><ol><li>登录后台管理系统</li><li>设置网站基本信息</li><li>创建分类</li><li>发布第一篇文章</li><li>自定义主题样式</li></ol>',
                        '详细的系统使用指南，帮助您快速上手。',
                        $admin_id,
                        $category_id,
                        'published'
                    ]
                ];
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO articles (title, slug, content, excerpt, author_id, category_id, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                foreach ($articles as $article) {
                    $stmt->execute($article);
                }
                echo "<div class='success'>✅ 示例文章创建成功</div>";
                
                $completed_steps[] = 'sample_data';
                echo "</div>";
            }
            
            // 步骤7：生成配置文件
            echo "<div class='step' id='step-config_file'>";
            echo "<h3>⚙️ {$steps['config_file']}</h3>";
            
            $config_content = "<?php
/**
 * CMS系统配置文件
 * 自动生成于: " . date('Y-m-d H:i:s') . "
 */

// 数据库配置
define('DB_HOST', '{$db_host}');
define('DB_NAME', '{$db_name}');
define('DB_USER', '{$db_user}');
define('DB_PASS', '{$db_pass}');

// 网站配置
define('SITE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']));
define('SITE_TITLE', 'CMS管理系统');

// 上传配置
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads');

// 安全配置
define('SESSION_LIFETIME', 7200); // 2小时

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告
if (\$_SERVER['HTTP_HOST'] === 'localhost' || strpos(\$_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}

// 会话配置
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params(SESSION_LIFETIME);

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>";
            
            // 创建includes目录
            if (!is_dir('includes')) {
                mkdir('includes', 0755, true);
            }
            
            if (file_put_contents('includes/config.php', $config_content)) {
                echo "<div class='success'>✅ 配置文件生成成功 (includes/config.php)</div>";
            } else {
                echo "<div class='error'>❌ 配置文件生成失败，请手动创建</div>";
                echo "<div class='code'>{$config_content}</div>";
            }
            
            $completed_steps[] = 'config_file';
            echo "</div>";
            
            // 创建锁定文件
            file_put_contents($lock_file, "数据库初始化完成\n时间: " . date('Y-m-d H:i:s') . "\n");
            
            // 显示完成信息
            echo "<div class='step completed'>";
            echo "<h3>🎉 初始化完成！</h3>";
            echo "<div class='success'>";
            echo "<strong>恭喜！CMS系统初始化成功！</strong><br><br>";
            echo "<strong>管理员登录信息：</strong><br>";
            echo "用户名: {$admin_username}<br>";
            echo "密码: {$admin_password}<br>";
            echo "邮箱: {$admin_email}<br><br>";
            echo "<strong>下一步操作：</strong><br>";
            echo "1. 立即删除此初始化脚本<br>";
            echo "2. 登录后台修改默认密码<br>";
            echo "3. 配置网站基本信息<br>";
            echo "4. 开始发布内容<br>";
            echo "</div>";
            
            echo "<a href='admin/login.php' class='btn btn-success'>🚪 前往登录页面</a>";
            echo "<a href='debug_login.php' class='btn'>🔧 运行登录诊断</a>";
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div class='step error'>";
            echo "<h3>❌ 初始化失败</h3>";
            echo "<div class='error'>数据库错误: " . $e->getMessage() . "</div>";
            echo "<div class='info'>常见解决方案：<br>";
            echo "1. 检查数据库连接信息是否正确<br>";
            echo "2. 确认MySQL服务正在运行<br>";
            echo "3. 验证数据库用户权限<br>";
            echo "4. 检查数据库名称是否合法<br>";
            echo "</div>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div class='step error'>";
            echo "<h3>❌ 初始化失败</h3>";
            echo "<div class='error'>系统错误: " . $e->getMessage() . "</div>";
            echo "</div>";
        }
    }
} else {
    // 显示初始化表单
    echo "<h2>📝 数据库配置</h2>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='init'>";
    
    echo "<div class='form-group'>";
    echo "<label>数据库主机</label>";
    echo "<input type='text' name='db_host' value='localhost' required>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>数据库名称</label>";
    echo "<input type='text' name='db_name' value='cms_website' required>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>数据库用户名</label>";
    echo "<input type='text' name='db_user' value='root' required>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>数据库密码</label>";
    echo "<input type='password' name='db_pass' placeholder='留空如果没有密码'>";
    echo "</div>";
    
    echo "<h2>👤 管理员配置</h2>";
    
    echo "<div class='form-group'>";
    echo "<label>管理员用户名</label>";
    echo "<input type='text' name='admin_username' value='admin' required>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>管理员邮箱</label>";
    echo "<input type='email' name='admin_email' value='admin@example.com' required>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>管理员密码</label>";
    echo "<input type='password' name='admin_password' value='123456' required>";
    echo "<div class='info'>建议使用强密码，登录后可以修改</div>";
    echo "</div>";
    
    echo "<h2>🎛️ 其他选项</h2>";
    
    echo "<div class='form-group'>";
    echo "<label>";
    echo "<input type='checkbox' name='create_sample_data' checked> 创建示例数据（分类和文章）";
    echo "</label>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠️ 重要提醒：</strong><br>";
    echo "1. 如果数据库已存在，部分数据可能会被保留<br>";
    echo "2. 确保数据库用户有创建数据库和表的权限<br>";
    echo "3. 初始化完成后请立即删除此脚本<br>";
    echo "</div>";
    
    echo "<button type='submit' class='btn btn-success'>🚀 开始初始化</button>";
    echo "</form>";
}
?>

        <div style="margin-top: 30px; text-align: center; color: #7f8c8d;">
            <p><strong>CMS系统初始化工具</strong> | 生成时间: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>