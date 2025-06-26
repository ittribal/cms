<?php
// ==================== admin/users/edit.php - 编辑用户 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('users.edit');

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: index.php');
    exit;
}

// 获取用户信息
$user = $db->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$user_id]);
if (!$user) {
    set_flash_message('用户不存在', 'error');
    header('Location: index.php');
    exit;
}

// 获取角色列表
$roles = $db->fetchAll("SELECT * FROM admin_roles ORDER BY id");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'CSRF验证失败';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role_id = intval($_POST['role_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        
        // 验证
        if (empty($username)) {
            $errors[] = '用户名不能为空';
        } elseif (strlen($username) < 3) {
            $errors[] = '用户名长度至少3位';
        }
        
        if (empty($email)) {
            $errors[] = '邮箱不能为空';
        } elseif (!validate_email($email)) {
            $errors[] = '邮箱格式不正确';
        }
        
        if (!empty($password)) {
            $password_errors = check_password_strength($password);
            $errors = array_merge($errors, $password_errors);
            
            if ($password !== $password_confirm) {
                $errors[] = '两次输入的密码不一致';
            }
        }
        
        if ($role_id <= 0) {
            $errors[] = '请选择用户角色';
        }
        
        // 检查用户名和邮箱唯一性（排除当前用户）
        if (empty($errors)) {
            $existing_user = $db->fetchOne("SELECT id FROM admin_users WHERE username = ? AND id != ?", [$username, $user_id]);
            if ($existing_user) {
                $errors[] = '用户名已存在';
            }
            
            $existing_email = $db->fetchOne("SELECT id FROM admin_users WHERE email = ? AND id != ?", [$email, $user_id]);
            if ($existing_email) {
                $errors[] = '邮箱已存在';
            }
        }
        
        if (empty($errors)) {
            try {
                $update_data = [
                    'username' => $username,
                    'email' => $email,
                    'role_id' => $role_id,
                    'status' => $status
                ];
                
                if (!empty($password)) {
                    $update_data['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                $db->update('admin_users', $update_data, "id = $user_id");
                
                $auth->logAction($_SESSION['user_id'], 'user_update', 'admin_users', $user_id);
                set_flash_message('用户更新成功', 'success');
                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                $errors[] = '保存失败: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑用户 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">编辑用户</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-outline">
                        <span class="icon">←</span> 返回列表
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="content-card">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">用户名 *</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">邮箱地址 *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">新密码</label>
                            <input type="password" id="password" name="password" class="form-control">
                            <small class="form-help">留空表示不修改密码</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">确认新密码</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="role_id">用户角色 *</label>
                            <select id="role_id" name="role_id" required class="form-control">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" 
                                            <?= (($_POST['role_id'] ?? $user['role_id']) == $role['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">状态</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?= (($_POST['status'] ?? $user['status']) === 'active') ? 'selected' : '' ?>>正常</option>
                                <option value="inactive" <?= (($_POST['status'] ?? $user['status']) === 'inactive') ? 'selected' : '' ?>>禁用</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="user-info-section">
                        <h3>用户信息</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>用户ID:</label>
                                <span><?= $user['id'] ?></span>
                            </div>
                            <div class="info-item">
                                <label>创建时间:</label>
                                <span><?= date('Y-m-d H:i:s', strtotime($user['created_at'])) ?></span>
                            </div>
                            <div class="info-item">
                                <label>最后登录:</label>
                                <span><?= $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : '从未登录' ?></span>
                            </div>
                            <div class="info-item">
                                <label>更新时间:</label>
                                <span><?= date('Y-m-d H:i:s', strtotime($user['updated_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="icon">💾</span> 保存更改
                        </button>
                        <a href="index.php" class="btn btn-outline">取消</a>
                        <?php if ($auth->hasPermission('users.delete') && $user['id'] != $_SESSION['user_id']): ?>
                            <a href="delete.php?id=<?= $user['id'] ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('确定要删除此用户吗？')">
                                <span class="icon">🗑️</span> 删除用户
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <style>
        .user-info-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .user-info-section h3 {
            margin-bottom: 1rem;
            color: #374151;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
    </style>
</body>
</html>