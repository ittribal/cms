<?php
// ==================== admin/users/add.php - 添加用户 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('users.create');

// 获取角色列表
$roles = $db->fetchAll("SELECT * FROM admin_roles ORDER BY id");

$errors = [];
$success = '';

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
        
        if (empty($password)) {
            $errors[] = '密码不能为空';
        } else {
            $password_errors = check_password_strength($password);
            $errors = array_merge($errors, $password_errors);
        }
        
        if ($password !== $password_confirm) {
            $errors[] = '两次输入的密码不一致';
        }
        
        if ($role_id <= 0) {
            $errors[] = '请选择用户角色';
        }
        
        // 检查用户名和邮箱唯一性
        if (empty($errors)) {
            $existing_user = $db->fetchOne("SELECT id FROM admin_users WHERE username = ?", [$username]);
            if ($existing_user) {
                $errors[] = '用户名已存在';
            }
            
            $existing_email = $db->fetchOne("SELECT id FROM admin_users WHERE email = ?", [$email]);
            if ($existing_email) {
                $errors[] = '邮箱已存在';
            }
        }
        
        if (empty($errors)) {
            try {
                $user_id = $db->insert('admin_users', [
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role_id' => $role_id,
                    'status' => $status
                ]);
                
                $auth->logAction($_SESSION['user_id'], 'user_create', 'admin_users', $user_id);
                set_flash_message('用户添加成功', 'success');
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
    <title>添加用户 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">添加用户</h1>
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
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   class="form-control">
                            <small class="form-help">用户名长度3-20位，只能包含字母、数字和下划线</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">邮箱地址 *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">密码 *</label>
                            <input type="password" id="password" name="password" required class="form-control">
                            <small class="form-help">密码长度至少8位，包含大小写字母和数字</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">确认密码 *</label>
                            <input type="password" id="password_confirm" name="password_confirm" required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="role_id">用户角色 *</label>
                            <select id="role_id" name="role_id" required class="form-control">
                                <option value="">请选择角色</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" 
                                            <?= (($_POST['role_id'] ?? '') == $role['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">状态</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>正常</option>
                                <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>禁用</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="icon">💾</span> 保存用户
                        </button>
                        <a href="index.php" class="btn btn-outline">取消</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</body>
</html>