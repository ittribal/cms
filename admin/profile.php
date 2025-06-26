<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录状态
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = '个人设置';
$currentUser = $auth->getCurrentUser();
$message = '';
$error = '';

// 处理表单提交
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_profile':
            $result = updateProfile($_POST);
            break;
        case 'change_password':
            $result = changePassword($_POST);
            break;
        case 'update_avatar':
            $result = updateAvatar($_FILES['avatar'] ?? null);
            break;
        case 'update_preferences':
            $result = updatePreferences($_POST);
            break;
    }
    
    if (isset($result)) {
        if ($result['success']) {
            $message = $result['message'];
            // 重新获取用户信息
            $currentUser = $auth->getCurrentUser();
        } else {
            $error = $result['message'];
        }
    }
}

// 更新个人资料
function updateProfile($data) {
    global $db, $auth;
    
    try {
        // 验证必填字段
        if (empty($data['username']) || empty($data['email'])) {
            return ['success' => false, 'message' => '用户名和邮箱不能为空'];
        }
        
        // 验证邮箱格式
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => '邮箱格式不正确'];
        }
        
        $userId = $auth->getCurrentUser()['id'];
        
        // 检查用户名和邮箱是否被其他用户使用
        $existing = $db->fetchOne(
            "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?", 
            [$data['username'], $data['email'], $userId]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => '用户名或邮箱已被其他用户使用'];
        }
        
        // 更新用户信息
        $updateData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'real_name' => $data['real_name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'bio' => $data['bio'] ?? '',
            'website' => $data['website'] ?? '',
            'location' => $data['location'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $setClause = implode(' = ?, ', array_keys($updateData)) . ' = ?';
        $sql = "UPDATE users SET {$setClause} WHERE id = ?";
        $params = array_merge(array_values($updateData), [$userId]);
        
        if ($db->execute($sql, $params)) {
            $auth->logAction('更新个人资料', '更新用户信息');
            return ['success' => true, 'message' => '个人资料更新成功'];
        }
        
        return ['success' => false, 'message' => '更新失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '更新失败：' . $e->getMessage()];
    }
}

// 修改密码
function changePassword($data) {
    global $db, $auth;
    
    try {
        $userId = $auth->getCurrentUser()['id'];
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        
        // 验证当前密码
        $user = $db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => '当前密码不正确'];
        }
        
        // 验证新密码
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => '新密码至少需要6位字符'];
        }
        
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => '两次输入的新密码不一致'];
        }
        
        // 更新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        
        if ($db->execute($sql, [$hashedPassword, $userId])) {
            $auth->logAction('修改密码', '用户修改登录密码');
            return ['success' => true, 'message' => '密码修改成功'];
        }
        
        return ['success' => false, 'message' => '密码修改失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '密码修改失败：' . $e->getMessage()];
    }
}

// 更新头像
function updateAvatar($file) {
    global $db, $auth;
    
    try {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => '文件上传失败'];
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // 验证文件类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => '只支持JPG、PNG、GIF格式的图片'];
        }
        
        // 验证文件大小
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => '图片大小不能超过2MB'];
        }
        
        // 创建上传目录
        $uploadDir = '../uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // 生成文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $auth->getCurrentUser()['id'] . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $filename;
        
        // 删除旧头像
        $oldAvatar = $auth->getCurrentUser()['avatar'];
        if ($oldAvatar && file_exists('../' . $oldAvatar)) {
            unlink('../' . $oldAvatar);
        }
        
        // 移动文件
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $relativePath = 'uploads/avatars/' . $filename;
            
            // 更新数据库
            $sql = "UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?";
            if ($db->execute($sql, [$relativePath, $auth->getCurrentUser()['id']])) {
                $auth->logAction('更新头像', '上传新的用户头像');
                return ['success' => true, 'message' => '头像更新成功'];
            } else {
                unlink($filePath);
                return ['success' => false, 'message' => '头像更新失败'];
            }
        }
        
        return ['success' => false, 'message' => '文件保存失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '头像更新失败：' . $e->getMessage()];
    }
}

// 更新偏好设置
function updatePreferences($data) {
    global $db, $auth;
    
    try {
        $userId = $auth->getCurrentUser()['id'];
        $preferences = [
            'theme' => $data['theme'] ?? 'light',
            'language' => $data['language'] ?? 'zh-CN',
            'timezone' => $data['timezone'] ?? 'Asia/Shanghai',
            'email_notifications' => isset($data['email_notifications']) ? 1 : 0,
            'browser_notifications' => isset($data['browser_notifications']) ? 1 : 0
        ];
        
        // 将偏好设置保存为JSON
        $preferencesJson = json_encode($preferences);
        
        $sql = "UPDATE users SET preferences = ?, updated_at = NOW() WHERE id = ?";
        if ($db->execute($sql, [$preferencesJson, $userId])) {
            $auth->logAction('更新偏好设置', '修改个人偏好配置');
            return ['success' => true, 'message' => '偏好设置更新成功'];
        }
        
        return ['success' => false, 'message' => '偏好设置更新失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '偏好设置更新失败：' . $e->getMessage()];
    }
}

// 获取用户偏好设置
function getUserPreferences() {
    global $currentUser;
    
    $preferences = json_decode($currentUser['preferences'] ?? '{}', true);
    return array_merge([
        'theme' => 'light',
        'language' => 'zh-CN',
        'timezone' => 'Asia/Shanghai',
        'email_notifications' => 1,
        'browser_notifications' => 1
    ], $preferences);
}

$preferences = getUserPreferences();

// 获取用户活动统计
$userStats = [
    'login_count' => $db->fetchOne("SELECT COUNT(*) as count FROM admin_logs WHERE user_id = ? AND action LIKE '%登录%'", [$currentUser['id']])['count'],
    'article_count' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE author_id = ?", [$currentUser['id']])['count'],
    'last_login' => $db->fetchOne("SELECT created_at FROM admin_logs WHERE user_id = ? AND action LIKE '%登录%' ORDER BY created_at DESC LIMIT 1", [$currentUser['id']])['created_at'] ?? null,
    'total_actions' => $db->fetchOne("SELECT COUNT(*) as count FROM admin_logs WHERE user_id = ?", [$currentUser['id']])['count']
];

include '../templates/admin_header.php';
?>

<link rel="stylesheet" href="css/profile.css">

<div class="profile-page">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-user-circle"></i> 个人设置</h1>
                <p>管理您的个人资料、安全设置和偏好配置</p>
            </div>
            <div class="header-actions">
                <a href="../public/" target="_blank" class="btn btn-secondary">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存头像
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('avatarUploadModal')">
                            取消
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="js/profile.js"></script>

<?php include '../templates/admin_footer.php'; ?><i class="fas fa-external-link-alt"></i> 查看网站
                </a>
            </div>
        </div>

        <!-- 用户概览卡片 -->
        <div class="user-overview">
            <div class="user-card">
                <div class="user-avatar-section">
                    <div class="user-avatar-large">
                        <?php if ($currentUser['avatar']): ?>
                            <img src="../<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="头像">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($currentUser['username'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-actions">
                        <button onclick="showAvatarUpload()" class="btn btn-sm btn-primary">
                            <i class="fas fa-camera"></i> 更换头像
                        </button>
                    </div>
                </div>
                
                <div class="user-info-section">
                    <h2><?php echo htmlspecialchars($currentUser['username']); ?></h2>
                    <p class="user-email"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                    <div class="user-role">
                        <span class="role-badge role-<?php echo $currentUser['role']; ?>">
                            <?php
                            $roleLabels = [
                                'super_admin' => '超级管理员',
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'author' => '作者',
                                'subscriber' => '订阅者'
                            ];
                            echo $roleLabels[$currentUser['role']] ?? $currentUser['role'];
                            ?>
                        </span>
                    </div>
                    <div class="user-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            注册时间：<?php echo date('Y年m月d日', strtotime($currentUser['created_at'])); ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            最后登录：<?php echo $userStats['last_login'] ? date('Y-m-d H:i', strtotime($userStats['last_login'])) : '从未登录'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="user-stats-section">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($userStats['article_count']); ?></div>
                        <div class="stat-label">发布文章</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($userStats['login_count']); ?></div>
                        <div class="stat-label">登录次数</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($userStats['total_actions']); ?></div>
                        <div class="stat-label">操作次数</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 设置选项卡 -->
        <div class="settings-tabs">
            <button class="tab-btn active" data-tab="profile">
                <i class="fas fa-user"></i> 基本资料
            </button>
            <button class="tab-btn" data-tab="security">
                <i class="fas fa-shield-alt"></i> 安全设置
            </button>
            <button class="tab-btn" data-tab="preferences">
                <i class="fas fa-cog"></i> 偏好设置
            </button>
            <button class="tab-btn" data-tab="activity">
                <i class="fas fa-history"></i> 活动记录
            </button>
        </div>

        <!-- 基本资料 -->
        <div class="tab-content active" id="profile">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> 基本资料</h3>
                </div>
                <form method="POST" class="profile-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="username">用户名 <span class="required">*</span></label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="email">邮箱地址 <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="real_name">真实姓名</label>
                            <input type="text" id="real_name" name="real_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentUser['real_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="phone">联系电话</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="website">个人网站</label>
                            <input type="url" id="website" name="website" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentUser['website'] ?? ''); ?>" 
                                   placeholder="https://example.com">
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="location">所在地</label>
                            <input type="text" id="location" name="location" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentUser['location'] ?? ''); ?>" 
                                   placeholder="城市, 国家">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">个人简介</label>
                        <textarea id="bio" name="bio" class="form-control" rows="4" 
                                  placeholder="介绍一下您自己..."><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存更改
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 安全设置 -->
        <div class="tab-content" id="security">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-shield-alt"></i> 修改密码</h3>
                </div>
                <form method="POST" class="security-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">当前密码 <span class="required">*</span></label>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="new_password">新密码 <span class="required">*</span></label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" minlength="6" required>
                            <small class="form-text">密码长度至少6位字符</small>
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="confirm_password">确认新密码 <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" minlength="6" required>
                        </div>
                    </div>
                    
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill"></div>
                        </div>
                        <div class="strength-text">密码强度：<span>请输入密码</span></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> 更新密码
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- 登录记录 -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> 最近登录记录</h3>
                </div>
                <div class="login-history">
                    <?php
                    $loginLogs = $db->fetchAll("
                        SELECT * FROM admin_logs 
                        WHERE user_id = ? AND action LIKE '%登录%' 
                        ORDER BY created_at DESC 
                        LIMIT 10
                    ", [$currentUser['id']]);
                    
                    if (empty($loginLogs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>暂无登录记录</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($loginLogs as $log): ?>
                            <div class="login-item">
                                <div class="login-icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <div class="login-info">
                                    <div class="login-action"><?php echo htmlspecialchars($log['action']); ?></div>
                                    <div class="login-meta">
                                        <span class="login-time"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></span>
                                        <span class="login-ip">IP: <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                                <div class="login-status">
                                    <span class="status-badge status-success">成功</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 偏好设置 -->
        <div class="tab-content" id="preferences">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> 偏好设置</h3>
                </div>
                <form method="POST" class="preferences-form">
                    <input type="hidden" name="action" value="update_preferences">
                    
                    <div class="form-section">
                        <h4>界面设置</h4>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="theme">主题</label>
                                <select id="theme" name="theme" class="form-control">
                                    <option value="light" <?php echo $preferences['theme'] === 'light' ? 'selected' : ''; ?>>浅色主题</option>
                                    <option value="dark" <?php echo $preferences['theme'] === 'dark' ? 'selected' : ''; ?>>深色主题</option>
                                    <option value="auto" <?php echo $preferences['theme'] === 'auto' ? 'selected' : ''; ?>>跟随系统</option>
                                </select>
                            </div>
                            
                            <div class="form-group col-6">
                                <label for="language">语言</label>
                                <select id="language" name="language" class="form-control">
                                    <option value="zh-CN" <?php echo $preferences['language'] === 'zh-CN' ? 'selected' : ''; ?>>简体中文</option>
                                    <option value="en-US" <?php echo $preferences['language'] === 'en-US' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">时区</label>
                            <select id="timezone" name="timezone" class="form-control">
                                <option value="Asia/Shanghai" <?php echo $preferences['timezone'] === 'Asia/Shanghai' ? 'selected' : ''; ?>>中国标准时间 (UTC+8)</option>
                                <option value="UTC" <?php echo $preferences['timezone'] === 'UTC' ? 'selected' : ''; ?>>协调世界时 (UTC)</option>
                                <option value="America/New_York" <?php echo $preferences['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>美国东部时间 (UTC-5)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>通知设置</h4>
                        <div class="notification-settings">
                            <div class="notification-item">
                                <div class="notification-info">
                                    <div class="notification-title">邮件通知</div>
                                    <div class="notification-desc">接收系统重要通知邮件</div>
                                </div>
                                <div class="notification-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="email_notifications" 
                                               <?php echo $preferences['email_notifications'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="notification-item">
                                <div class="notification-info">
                                    <div class="notification-title">浏览器通知</div>
                                    <div class="notification-desc">在浏览器中显示实时通知</div>
                                </div>
                                <div class="notification-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="browser_notifications" 
                                               <?php echo $preferences['browser_notifications'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存偏好
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 活动记录 -->
        <div class="tab-content" id="activity">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> 最近活动</h3>
                </div>
                <div class="activity-timeline">
                    <?php
                    $activities = $db->fetchAll("
                        SELECT * FROM admin_logs 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 20
                    ", [$currentUser['id']]);
                    
                    if (empty($activities)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>暂无活动记录</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-circle"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                                    <div class="activity-details"><?php echo htmlspecialchars($activity['details'] ?? ''); ?></div>
                                    <div class="activity-time"><?php echo date('Y-m-d H:i:s', strtotime($activity['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- 头像上传模态框 -->
    <div id="avatarUploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-camera"></i> 更换头像</h3>
                <button type="button" class="close" onclick="closeModal('avatarUploadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="avatarForm">
                    <input type="hidden" name="action" value="update_avatar">
                    
                    <div class="avatar-upload-area">
                        <div class="current-avatar">
                            <img id="avatarPreview" src="../<?php echo $currentUser['avatar'] ?? 'assets/default-avatar.png'; ?>" alt="当前头像">
                        </div>
                        
                        <div class="upload-controls">
                            <input type="file" id="avatarFile" name="avatar" accept="image/*" style="display: none;">
                            <button type="button" onclick="document.getElementById('avatarFile').click()" class="btn btn-primary">
                                <i class="fas fa-upload"></i> 选择图片
                            </button>
                            <small class="form-text">支持JPG、PNG、GIF格式，大小不超过2MB</small>
                        </div>
                    </div>