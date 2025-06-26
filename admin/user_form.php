<?php
// admin/user_form.php - 用户表单页面

$isEdit = $action === 'edit';
$user = null;

if ($isEdit) {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        header('Location: users.php?error=' . urlencode('用户不存在'));
        exit;
    }
}

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
        <div class="page-header">
            <div class="header-left">
                <h1>
                    <i class="fas fa-user-<?php echo $isEdit ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $isEdit ? '编辑用户' : '新建用户'; ?>
                </h1>
                <p><?php echo $isEdit ? '修改用户信息和权限设置' : '创建新的系统用户账户'; ?></p>
            </div>
            <div class="header-actions">
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </div>

        <div class="form-container">
            <form method="POST" class="user-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                <?php endif; ?>
                
                <!-- 基本信息 -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> 基本信息</h3>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="username">用户名 <span class="required">*</span></label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" 
                                   required maxlength="50">
                            <small class="form-text">用于登录的唯一标识符</small>
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="email">邮箱地址 <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                   required maxlength="100">
                            <small class="form-text">用于接收系统通知</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="real_name">真实姓名</label>
                            <input type="text" id="real_name" name="real_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['real_name'] ?? ''); ?>" 
                                   maxlength="50">
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="phone">联系电话</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   maxlength="20">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">个人简介</label>
                        <textarea id="bio" name="bio" class="form-control" rows="3" 
                                  maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <small class="form-text">简短介绍用户背景信息</small>
                    </div>
                </div>
                
                <!-- 账户设置 -->
                <div class="form-section">
                    <h3><i class="fas fa-cog"></i> 账户设置</h3>
                    
                    <div class="form-row">
                        <div class="form-group col-4">
                            <label for="role">用户角色 <span class="required">*</span></label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="subscriber" <?php echo ($user['role'] ?? '') === 'subscriber' ? 'selected' : ''; ?>>订阅者</option>
                                <option value="author" <?php echo ($user['role'] ?? '') === 'author' ? 'selected' : ''; ?>>作者</option>
                                <option value="editor" <?php echo ($user['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>编辑</option>
                                <?php if ($auth->hasPermission('user.assign_admin')): ?>
                                    <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>管理员</option>
                                    <option value="super_admin" <?php echo ($user['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>超级管理员</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-4">
                            <label for="status">账户状态</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?php echo ($user['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>活跃</option>
                                <option value="inactive" <?php echo ($user['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>禁用</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-4">
                            <label for="avatar">头像上传</label>
                            <input type="file" id="avatar" name="avatar" class="form-control" 
                                   accept="image/*">
                            <small class="form-text">支持JPG、PNG格式，大小不超过2MB</small>
                        </div>
                    </div>
                    
                    <?php if ($user && $user['avatar']): ?>
                        <div class="current-avatar">
                            <label>当前头像</label>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                 alt="当前头像" class="avatar-preview">
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 密码设置 -->
                <div class="form-section">
                    <h3><i class="fas fa-key"></i> 密码设置</h3>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="password">
                                <?php echo $isEdit ? '新密码（留空不修改）' : '密码'; ?>
                                <?php if (!$isEdit): ?><span class="required">*</span><?php endif; ?>
                            </label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   minlength="6" <?php echo $isEdit ? '' : 'required'; ?>>
                            <small class="form-text">密码长度至少6位字符</small>
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="password_confirm">确认密码</label>
                            <input type="password" id="password_confirm" name="password_confirm" 
                                   class="form-control" minlength="6">
                            <small class="form-text">请再次输入密码进行确认</small>
                        </div>
                    </div>
                    
                    <?php if ($isEdit): ?>
                        <div class="password-actions">
                            <button type="button" onclick="generatePassword()" class="btn btn-info btn-sm">
                                <i class="fas fa-dice"></i> 生成随机密码
                            </button>
                            <button type="button" onclick="sendPasswordReset()" class="btn btn-warning btn-sm">
                                <i class="fas fa-envelope"></i> 发送重置邮件
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 权限预览 -->
                <div class="form-section">
                    <h3><i class="fas fa-shield-alt"></i> 权限预览</h3>
                    <div class="permissions-preview" id="permissionsPreview">
                        <!-- 权限内容将通过JavaScript动态加载 -->
                    </div>
                </div>
                
                <!-- 操作按钮 -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $isEdit ? '更新用户' : '创建用户'; ?>
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 取消
                    </a>
                    
                    <?php if ($isEdit && $user['id'] != $currentUser['id']): ?>
                        <button type="button" onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                class="btn btn-danger">
                            <i class="fas fa-trash"></i> 删除用户
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </main>
</div>

<style>
.form-container {
    max-width: 900px;
    margin: 0 auto;
}

.user-form {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.form-section {
    margin-bottom: 2.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 1rem;
}

.form-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.form-row .col-4 {
    grid-column: span 1;
}

.form-row .col-6 {
    grid-column: span 1;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.required {
    color: #e74c3c;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-text {
    color: #6c757d;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.current-avatar {
    margin-top: 1rem;
}

.avatar-preview {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #dee2e6;
}

.password-actions {
    margin-top: 1rem;
    display: flex;
    gap: 0.5rem;
}

.permissions-preview {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    min-height: 100px;
}

.permission-group {
    margin-bottom: 1rem;
}

.permission-group h4 {
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.permission-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.permission-item {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    border: 1px solid #bbdefb;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
// 权限配置
const rolePermissions = {
    'subscriber': {
        '基本权限': ['查看已发布内容', '管理个人资料']
    },
    'author': {
        '基本权限': ['查看已发布内容', '管理个人资料'],
        '内容管理': ['创建文章', '编辑自己的文章', '上传文件']
    },
    'editor': {
        '基本权限': ['查看已发布内容', '管理个人资料'],
        '内容管理': ['创建文章', '编辑所有文章', '管理分类', '管理标签', '上传文件'],
        '评论管理': ['审核评论', '回复评论']
    },
    'admin': {
        '基本权限': ['查看已发布内容', '管理个人资料'],
        '内容管理': ['创建文章', '编辑所有文章', '删除文章', '管理分类', '管理标签', '上传文件'],
        '用户管理': ['查看用户', '编辑用户', '删除用户'],
        '评论管理': ['审核评论', '回复评论', '删除评论'],
        '系统管理': ['查看统计', '管理设置']
    },
    'super_admin': {
        '基本权限': ['查看已发布内容', '管理个人资料'],
        '内容管理': ['创建文章', '编辑所有文章', '删除文章', '管理分类', '管理标签', '上传文件'],
        '用户管理': ['查看用户', '编辑用户', '删除用户', '分配管理员权限'],
        '评论管理': ['审核评论', '回复评论', '删除评论'],
        '系统管理': ['查看统计', '管理设置', '系统备份', '查看日志']
    }
};

// 更新权限预览
function updatePermissionsPreview() {
    const role = document.getElementById('role').value;
    const preview = document.getElementById('permissionsPreview');
    
    if (!rolePermissions[role]) {
        preview.innerHTML = '<p class="text-muted">请选择用户角色</p>';
        return;
    }
    
    let html = '';
    const permissions = rolePermissions[role];
    
    for (const [group, items] of Object.entries(permissions)) {
        html += `<div class="permission-group">`;
        html += `<h4>${group}</h4>`;
        html += `<div class="permission-list">`;
        
        items.forEach(item => {
            html += `<span class="permission-item">${item}</span>`;
        });
        
        html += `</div></div>`;
    }
    
    preview.innerHTML = html;
}

// 生成随机密码
function generatePassword() {
    const length = 12;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    document.getElementById('password').value = password;
    document.getElementById('password_confirm').value = password;
    
    alert('随机密码已生成：' + password);
}

// 发送密码重置邮件
function sendPasswordReset() {
    const userId = document.querySelector('input[name="id"]').value;
    
    if (confirm('确定要发送密码重置邮件给该用户吗？')) {
        fetch('send_password_reset.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('密码重置邮件发送成功');
            } else {
                alert('发送失败：' + data.message);
            }
        })
        .catch(error => {
            alert('发送失败，请重试');
        });
    }
}

// 删除用户
function deleteUser(id) {
    if (confirm('确定要删除这个用户吗？删除后无法恢复。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input name="action" value="delete">
            <input name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// 验证表单
function validateForm() {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    
    if (password && password !== passwordConfirm) {
        alert('两次输入的密码不一致');
        return false;
    }
    
    return true;
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 初始化权限预览
    updatePermissionsPreview();
    
    // 角色变更时更新权限预览
    document.getElementById('role').addEventListener('change', updatePermissionsPreview);
    
    // 表单提交验证
    document.querySelector('.user-form').addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
    
    // 密码确认实时验证
    const passwordConfirm = document.getElementById('password_confirm');
    passwordConfirm.addEventListener('input', function() {
        const password = document.getElementById('password').value;
        if (this.value && password !== this.value) {
            this.setCustomValidity('密码不一致');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php include '../templates/admin_footer.php'; ?>