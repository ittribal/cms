<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

$auth = new Auth();

// 检查登录和权限
if (!$auth->isLoggedIn() || !$auth->hasPermission('log.view')) {
    http_response_code(403);
    echo json_encode(['error' => '无权限']);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => '缺少参数']);
    exit;
}

$db = Database::getInstance();
$log = $db->fetchOne("SELECT * FROM admin_logs WHERE id = ?", [$id]);

if (!$log) {
    http_response_code(404);
    echo json_encode(['error' => '日志不存在']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($log);
?>
3. 清理日志API (admin/clear_logs.php)
php
<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

$auth = new Auth();

// 检查登录和权限
if (!$auth->isLoggedIn() || !$auth->hasPermission('log.view')) {
    http_response_code(403);
    echo json_encode(['error' => '无权限']);
    exit;
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '方法不允许']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$days = $input['days'] ?? 30;

if ($days < 1 || $days > 365) {
    http_response_code(400);
    echo json_encode(['error' => '天数范围应在1-365之间']);
    exit;
}

$db = Database::getInstance();

try {
    $sql = "DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $db->execute($sql, [$days]);
    
    $deletedCount = $db->getConnection()->lastAffectedRows ?? 0;
    
    // 记录清理操作
    $auth->logAction('清理日志', 'admin_logs', null, null, ['days' => $days, 'deleted_count' => $deletedCount]);
    
    echo json_encode([
        'success' => true,
        'message' => "成功清理了 {$deletedCount} 条日志记录"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '清理失败: ' . $e->getMessage()]);
}
?>
4. 退出登录页面 (admin/logout.php)
php
<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    $auth->logAction('用户登出');
    $auth->logout();
}

header('Location: login.php?message=已成功退出登录');
exit;
?>
5. 个人资料页面 (admin/profile.php)
php
<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

$auth = new Auth();

// 检查登录状态
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$error = '';

$user = $auth->getCurrentUser();

// 处理个人资料更新
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        if (empty($username) || empty($email)) {
            $error = '用户名和邮箱不能为空';
        } else {
            // 检查用户名和邮箱是否已被其他用户使用
            $existing = $db->fetchOne("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?", 
                                     [$username, $email, $user['id']]);
            if ($existing) {
                $error = '用户名或邮箱已被其他用户使用';
            } else {
                $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
                if ($db->execute($sql, [$username, $email, $user['id']])) {
                    $auth->logAction('更新个人资料');
                    $message = '个人资料更新成功';
                    $user = $auth->getCurrentUser(); // 重新获取用户信息
                    $_SESSION['username'] = $username; // 更新会话中的用户名
                } else {
                    $error = '更新失败';
                }
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = '所有密码字段都是必填的';
        } elseif ($newPassword !== $confirmPassword) {
            $error = '新密码和确认密码不匹配';
        } elseif (strlen($newPassword) < 6) {
            $error = '新密码至少需要6位字符';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = '当前密码不正确';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            if ($db->execute($sql, [$hashedPassword, $user['id']])) {
                $auth->logAction('修改密码');
                $message = '密码修改成功';
            } else {
                $error = '密码修改失败';
            }
        }
    }
}

// 获取用户的活动统计
$userStats = [
    'articles_count' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE author_id = ?", [$user['id']])['count'],
    'published_articles' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE author_id = ? AND status = 'published'", [$user['id']])['count'],
    'total_views' => $db->fetchOne("SELECT SUM(views) as total FROM articles WHERE author_id = ?", [$user['id']])['total'] ?? 0,
    'login_count' => $db->fetchOne("SELECT COUNT(*) as count FROM admin_logs WHERE user_id = ? AND action = '用户登录'", [$user['id']])['count']
];

// 获取最近的活动记录
$recentActivities = $db->fetchAll("
    SELECT action, created_at, ip_address 
    FROM admin_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10", [$user['id']]);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人资料 - CMS后台</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include '../templates/admin_header.php'; ?>
    
    <div class="admin-container">
        <main class="main-content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>个人资料</h1>
            </div>
            
            <div class="profile-container">
                <!-- 用户信息卡片 -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p class="role"><?php echo htmlspecialchars($user['role_name']); ?></p>
                        <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="join-date">注册时间: <?php echo date('Y年m月d日', strtotime($user['created_at'])); ?></p>
                        <p class="last-login">最后登录: <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : '从未登录'; ?></p>
                    </div>
                </div>
                
                <!-- 统计信息 -->
                <div class="stats-section">
                    <h3>我的统计</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $userStats['articles_count']; ?></div>
                            <div class="stat-label">总文章数</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $userStats['published_articles']; ?></div>
                            <div class="stat-label">已发布</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($userStats['total_views']); ?></div>
                            <div class="stat-label">总浏览量</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $userStats['login_count']; ?></div>
                            <div class="stat-label">登录次数</div>
                        </div>
                    </div>
                </div>
                
                <!-- 编辑表单 -->
                <div class="profile-forms">
                    <!-- 个人资料编辑 -->
                    <div class="form-section">
                        <h3>编辑资料</h3>
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label for="username">用户名</label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">邮箱地址</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">更新资料</button>
                        </form>
                    </div>
                    
                    <!-- 密码修改 -->
                    <div class="form-section">
                        <h3>修改密码</h3>
                        <form method="POST" class="password-form">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password">当前密码</label>
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       class="form-control" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">新密码</label>
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control" 
                                       minlength="6"
                                       required>
                                <small class="form-text">至少6位字符</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">确认新密码</label>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-control" 
                                       minlength="6"
                                       required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">修改密码</button>
                        </form>
                    </div>
                </div>
                
                <!-- 最近活动 -->
                <div class="activity-section">
                    <h3>最近活动</h3>
                    <div class="activity-list">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                                <div class="activity-meta">
                                    <span class="activity-time"><?php echo date('Y-m-d H:i', strtotime($activity['created_at'])); ?></span>
                                    <span class="activity-ip">IP: <?php echo htmlspecialchars($activity['ip_address']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recentActivities)): ?>
                            <div class="no-activity">暂无活动记录</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // 密码确认验证
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('密码不匹配');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            grid-template-areas: 
                "card stats"
                "card forms"
                "activity activity";
        }
        
        .profile-card {
            grid-area: card;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
            text-align: center;
        }
        
        .profile-avatar {
            margin-bottom: 1.5rem;
        }
        
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
            margin: 0 auto;
        }
        
        .profile-info h2 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .role {
            color: #3498db;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .profile-info p {
            margin-bottom: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .stats-section {
            grid-area: stats;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-section h3 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .profile-forms {
            grid-area: forms;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-section h3 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .activity-section {
            grid-area: activity;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .activity-section h3 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }
        
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-action {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .activity-meta {
            display: flex;
            flex-direction: column;
            text-align: right;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .no-activity {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
        }
        
        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 968px) {
            .profile-container {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "card"
                    "stats"
                    "forms"
                    "activity";
            }
            
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</body>
</html>