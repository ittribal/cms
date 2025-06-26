<?php
/**
 * CMS登录问题调试脚本
 * 用于诊断登录功能的各种问题
 * 使用完毕后请删除此文件
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS登录问题调试</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
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
        .test-section {
            border: 1px solid #ddd;
            margin: 20px 0;
            padding: 20px;
            border-radius: 5px;
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
        .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 CMS登录问题调试工具</h1>
        <div class="warning">
            <strong>⚠️ 安全提醒：</strong> 此文件包含敏感信息，调试完成后请立即删除！
        </div>

<?php
echo "<h2>📋 系统环境检查</h2>";

// 检查PHP版本
echo "<div class='test-section'>";
echo "<h3>PHP环境</h3>";
echo "<p><strong>PHP版本:</strong> " . PHP_VERSION . "</p>";

if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "<div class='success'>✅ PHP版本符合要求 (>= 7.4)</div>";
} else {
    echo "<div class='error'>❌ PHP版本过低，建议升级到7.4或更高版本</div>";
}

// 检查必要的PHP扩展
$required_extensions = ['pdo', 'pdo_mysql', 'session', 'json'];
echo "<h4>必要的PHP扩展:</h4>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ {$ext} - 已安装</div>";
    } else {
        echo "<div class='error'>❌ {$ext} - 未安装</div>";
    }
}
echo "</div>";

// 1. 检查配置文件
echo "<div class='test-section'>";
echo "<h3>📁 配置文件检查</h3>";

$config_files = [
    'includes/config.php',
    'config.php',
    'includes/Database.php'
];

$config_found = false;
foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ 找到配置文件: {$file}</div>";
        $config_found = true;
        
        try {
            require_once $file;
            echo "<div class='success'>✅ 配置文件加载成功</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ 配置文件加载失败: " . $e->getMessage() . "</div>";
        }
        break;
    }
}

if (!$config_found) {
    echo "<div class='error'>❌ 未找到配置文件，请检查以下文件是否存在：</div>";
    foreach ($config_files as $file) {
        echo "<div class='code'>{$file}</div>";
    }
}

// 检查数据库常量
if (defined('DB_HOST')) {
    echo "<h4>数据库配置:</h4>";
    echo "<table>";
    echo "<tr><th>配置项</th><th>值</th></tr>";
    echo "<tr><td>DB_HOST</td><td>" . DB_HOST . "</td></tr>";
    echo "<tr><td>DB_NAME</td><td>" . (defined('DB_NAME') ? DB_NAME : '未定义') . "</td></tr>";
    echo "<tr><td>DB_USER</td><td>" . (defined('DB_USER') ? DB_USER : '未定义') . "</td></tr>";
    echo "<tr><td>DB_PASS</td><td>" . (defined('DB_PASS') ? '***已设置***' : '未定义') . "</td></tr>";
    echo "</table>";
} else {
    echo "<div class='error'>❌ 数据库配置常量未定义</div>";
}
echo "</div>";

// 2. 检查数据库连接
echo "<div class='test-section'>";
echo "<h3>🗄️ 数据库连接检查</h3>";

if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        echo "<div class='success'>✅ 数据库连接成功</div>";
        
        // 检查数据库版本
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "<p><strong>MySQL版本:</strong> {$version}</p>";
        
        // 使用Database类（如果存在）
        if (class_exists('Database')) {
            try {
                $db = Database::getInstance();
                echo "<div class='success'>✅ Database类初始化成功</div>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Database类初始化失败: " . $e->getMessage() . "</div>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ 数据库连接失败: " . $e->getMessage() . "</div>";
        echo "<div class='info'>常见解决方案：<br>";
        echo "1. 检查MySQL服务是否运行<br>";
        echo "2. 验证数据库名称、用户名、密码<br>";
        echo "3. 确认数据库用户有足够权限<br>";
        echo "4. 检查防火墙设置</div>";
    }
} else {
    echo "<div class='error'>❌ 数据库配置不完整，无法测试连接</div>";
}
echo "</div>";

// 3. 检查users表
if (isset($pdo)) {
    echo "<div class='test-section'>";
    echo "<h3>👥 用户表检查</h3>";
    
    try {
        // 检查users表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ users表存在</div>";
            
            // 检查表结构
            $columns = $pdo->query("DESCRIBE users")->fetchAll();
            echo "<h4>表结构:</h4>";
            echo "<table>";
            echo "<tr><th>字段</th><th>类型</th><th>是否为空</th><th>键</th><th>默认值</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // 检查用户数据
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "<p><strong>用户总数:</strong> {$userCount}</p>";
            
            if ($userCount > 0) {
                $users = $pdo->query("SELECT id, username, email, role, status, created_at FROM users LIMIT 10")->fetchAll();
                echo "<h4>用户列表 (最多显示10个):</h4>";
                echo "<table>";
                echo "<tr><th>ID</th><th>用户名</th><th>邮箱</th><th>角色</th><th>状态</th><th>创建时间</th></tr>";
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>{$user['username']}</td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>{$user['role']}</td>";
                    echo "<td>{$user['status']}</td>";
                    echo "<td>{$user['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // 检查管理员账号
                $adminUsers = $pdo->query("SELECT * FROM users WHERE role IN ('super_admin', 'admin') AND status = 'active'")->fetchAll();
                if (count($adminUsers) > 0) {
                    echo "<div class='success'>✅ 找到 " . count($adminUsers) . " 个活跃的管理员账号</div>";
                    foreach ($adminUsers as $admin) {
                        echo "<div class='info'>管理员: {$admin['username']} ({$admin['role']})</div>";
                    }
                } else {
                    echo "<div class='error'>❌ 未找到活跃的管理员账号</div>";
                }
            } else {
                echo "<div class='warning'>⚠️ users表为空，没有任何用户数据</div>";
            }
            
        } else {
            echo "<div class='error'>❌ users表不存在</div>";
            echo "<div class='info'>需要创建users表，可以使用初始化脚本</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ 检查users表时出错: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
}

// 4. 密码加密测试
echo "<div class='test-section'>";
echo "<h3>🔒 密码加密测试</h3>";

$testPasswords = ['123456', 'admin', 'password', 'admin123'];
echo "<h4>密码哈希测试:</h4>";
echo "<table>";
echo "<tr><th>原始密码</th><th>哈希值</th><th>验证结果</th></tr>";

foreach ($testPasswords as $pwd) {
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $verify = password_verify($pwd, $hash) ? '✅ 成功' : '❌ 失败';
    
    echo "<tr>";
    echo "<td>{$pwd}</td>";
    echo "<td>" . substr($hash, 0, 30) . "...</td>";
    echo "<td>{$verify}</td>";
    echo "</tr>";
}
echo "</table>";

// 测试数据库中的密码
if (isset($pdo) && isset($adminUsers)) {
    echo "<h4>数据库密码验证测试:</h4>";
    foreach ($adminUsers as $admin) {
        echo "<div class='info'>";
        echo "<strong>用户:</strong> {$admin['username']}<br>";
        echo "<strong>密码哈希:</strong> " . substr($admin['password'], 0, 30) . "...<br>";
        
        // 测试常见密码
        $commonPasswords = ['123456', 'admin', 'password', 'admin123', $admin['username']];
        $passwordFound = false;
        
        foreach ($commonPasswords as $testPwd) {
            if (password_verify($testPwd, $admin['password'])) {
                echo "<div class='warning'>⚠️ 检测到弱密码: {$testPwd}</div>";
                $passwordFound = true;
                break;
            }
        }
        
        if (!$passwordFound) {
            echo "<div class='success'>✅ 密码看起来比较安全（非常见弱密码）</div>";
        }
        echo "</div>";
    }
}
echo "</div>";

// 5. 会话检查
echo "<div class='test-section'>";
echo "<h3>🔄 会话配置检查</h3>";

echo "<table>";
echo "<tr><th>配置项</th><th>值</th></tr>";
echo "<tr><td>会话状态</td><td>" . 
    (session_status() === PHP_SESSION_ACTIVE ? '✅ 活跃' : 
    (session_status() === PHP_SESSION_NONE ? '❌ 未启动' : '⚠️ 禁用')) . "</td></tr>";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<tr><td>会话ID</td><td>" . session_id() . "</td></tr>";
echo "<tr><td>会话保存路径</td><td>" . session_save_path() . "</td></tr>";
echo "<tr><td>会话生存时间</td><td>" . ini_get('session.gc_maxlifetime') . " 秒</td></tr>";
echo "<tr><td>Cookie生存时间</td><td>" . ini_get('session.cookie_lifetime') . " 秒</td></tr>";
echo "<tr><td>使用Cookie</td><td>" . (ini_get('session.use_cookies') ? '✅ 是' : '❌ 否') . "</td></tr>";
echo "</table>";

// 检查会话目录权限
$session_path = session_save_path();
if ($session_path && is_dir($session_path)) {
    $writable = is_writable($session_path);
    echo "<div class='" . ($writable ? 'success' : 'error') . "'>";
    echo ($writable ? '✅' : '❌') . " 会话目录可写性: " . ($writable ? '可写' : '不可写');
    echo "</div>";
} else {
    echo "<div class='warning'>⚠️ 会话保存路径无效或不存在</div>";
}
echo "</div>";

// 6. 文件权限检查
echo "<div class='test-section'>";
echo "<h3>📁 文件权限检查</h3>";

$important_files = [
    'includes/config.php',
    'includes/Database.php',
    'includes/Auth.php',
    'admin/login.php',
    'uploads/',
    'logs/',
    'cache/'
];

echo "<table>";
echo "<tr><th>文件/目录</th><th>存在</th><th>权限</th><th>可读</th><th>可写</th></tr>";

foreach ($important_files as $file) {
    $exists = file_exists($file);
    $perms = $exists ? substr(sprintf('%o', fileperms($file)), -4) : '-';
    $readable = $exists ? (is_readable($file) ? '✅' : '❌') : '-';
    $writable = $exists ? (is_writable($file) ? '✅' : '❌') : '-';
    
    echo "<tr>";
    echo "<td>{$file}</td>";
    echo "<td>" . ($exists ? '✅' : '❌') . "</td>";
    echo "<td>{$perms}</td>";
    echo "<td>{$readable}</td>";
    echo "<td>{$writable}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 7. 登录功能测试
if (isset($pdo) && isset($adminUsers) && count($adminUsers) > 0) {
    echo "<div class='test-section'>";
    echo "<h3>🔐 登录功能模拟测试</h3>";
    
    // 模拟登录流程
    $testUser = $adminUsers[0]; // 使用第一个管理员账号
    
    echo "<div class='info'>";
    echo "<strong>测试账号:</strong> {$testUser['username']}<br>";
    echo "<strong>测试流程:</strong><br>";
    echo "1. 查找用户...<br>";
    echo "2. 验证密码...<br>";
    echo "3. 检查用户状态...<br>";
    echo "4. 设置会话...<br>";
    echo "</div>";
    
    // 模拟Auth类的login方法
    $username = $testUser['username'];
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<div class='success'>✅ 步骤1: 用户查找成功</div>";
            echo "<div class='success'>✅ 步骤3: 用户状态检查通过 (status: {$user['status']})</div>";
            
            // 这里不能测试密码，因为我们不知道真实密码
            echo "<div class='warning'>⚠️ 步骤2: 密码验证需要真实密码才能测试</div>";
            
            // 模拟会话设置
            $_SESSION['test_user_id'] = $user['id'];
            $_SESSION['test_username'] = $user['username'];
            $_SESSION['test_role'] = $user['role'];
            $_SESSION['test_logged_in'] = true;
            
            echo "<div class='success'>✅ 步骤4: 会话设置成功</div>";
            
            // 清理测试会话
            unset($_SESSION['test_user_id']);
            unset($_SESSION['test_username']);
            unset($_SESSION['test_role']);
            unset($_SESSION['test_logged_in']);
            
        } else {
            echo "<div class='error'>❌ 步骤1: 用户查找失败</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ 登录测试失败: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
}

// 8. 建议和解决方案
echo "<div class='test-section'>";
echo "<h3>💡 建议和解决方案</h3>";

$suggestions = [];

if (!$config_found) {
    $suggestions[] = "❌ 创建或修复配置文件 includes/config.php";
}

if (!isset($pdo)) {
    $suggestions[] = "❌ 修复数据库连接配置";
}

if (isset($pdo)) {
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
        if (!$tableExists) {
            $suggestions[] = "❌ 创建users表和基础数据";
        }
    } catch (Exception $e) {
        $suggestions[] = "❌ 检查数据库权限";
    }
}

if (isset($adminUsers) && count($adminUsers) === 0) {
    $suggestions[] = "❌ 创建管理员账号";
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $suggestions[] = "❌ 修复会话配置";
}

if (empty($suggestions)) {
    echo "<div class='success'>✅ 恭喜！没有发现明显问题，登录功能应该可以正常工作。</div>";
    echo "<div class='info'>如果仍然无法登录，请检查：<br>";
    echo "1. 确认输入的用户名和密码正确<br>";
    echo "2. 检查浏览器是否支持Cookie<br>";
    echo "3. 查看服务器错误日志<br>";
    echo "4. 确认没有其他安全限制</div>";
} else {
    echo "<div class='error'>发现以下问题需要解决：</div>";
    foreach ($suggestions as $suggestion) {
        echo "<div class='warning'>{$suggestion}</div>";
    }
}

echo "</div>";

// 9. 快速操作按钮
echo "<div class='test-section'>";
echo "<h3>🚀 快速操作</h3>";
echo "<p>根据检测结果，您可能需要以下操作：</p>";

echo "<a href='init_database.php' class='btn btn-success'>🔧 初始化数据库</a>";
echo "<a href='reset_admin.php' class='btn btn-danger'>🔑 重置管理员密码</a>";

if (isset($adminUsers) && count($adminUsers) > 0) {
    echo "<a href='admin/login.php' class='btn'>🚪 前往登录页面</a>";
}

echo "<div class='warning' style='margin-top: 20px;'>";
echo "<strong>⚠️ 重要提醒：</strong><br>";
echo "1. 调试完成后请立即删除此文件！<br>";
echo "2. 不要在生产环境中使用此脚本！<br>";
echo "3. 此脚本可能暴露敏感信息！";
echo "</div>";

echo "</div>";
?>

        <div class="test-section">
            <h3>📝 调试日志</h3>
            <div class="info">
                <strong>调试时间:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                <strong>服务器IP:</strong> <?php echo $_SERVER['SERVER_ADDR'] ?? '未知'; ?><br>
                <strong>客户端IP:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? '未知'; ?><br>
                <strong>用户代理:</strong> <?php echo $_SERVER['HTTP_USER_AGENT'] ?? '未知'; ?>
            </div>
        </div>
    </div>
</body>
</html>