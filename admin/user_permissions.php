<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录和权限
if (!$auth->isLoggedIn() || !$auth->hasPermission('user.assign_permissions')) {
    die('没有权限访问此页面');
}

$userId = $_GET['id'] ?? 0;
if (!$userId) {
    header('Location: users.php?error=' . urlencode('用户ID无效'));
    exit;
}

$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
if (!$user) {
    header('Location: users.php?error=' . urlencode('用户不存在'));
    exit;
}

$message = '';
$error = '';

// 处理权限更新
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_permissions') {
    try {
        $newRole = $_POST['role'];
        $customPermissions = $_POST['permissions'] ?? [];
        
        // 更新用户角色
        $db->execute("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?", [$newRole, $userId]);
        
        // 如果有自定义权限，保存到用户权限表（需要先创建表）
        // 这里简化处理，直接使用角色权限
        
        $auth->logAction('更新用户权限', "用户ID: {$userId}, 新角色: {$newRole}");
        $message = '权限更新成功';
        
        // 重新获取用户信息
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        
    } catch (Exception $e) {
        $error = '权限更新失败: ' . $e->getMessage();
    }
}

// 权限定义
$permissionDefinitions = [
    'content' => [
        'name' => '内容管理',
        'permissions' => [
            'article.view' => '查看文章',
            'article.create' => '创建文章',
            'article.edit' => '编辑文章',
            'article.edit_own' => '编辑自己的文章',
            'article.delete' => '删除文章',
            'article.delete_own' => '删除自己的文章',
            'article.publish' => '发布文章',
            'category.view' => '查看分类',
            'category.create' => '创建分类',
            'category.edit' => '编辑分类',
            'category.delete' => '删除分类',
            'tag.view' => '查看标签',
            'tag.create' => '创建标签',
            'tag.edit' => '编辑标签',
            'tag.delete' => '删除标签',
        ]
    ],
    'media' => [
        'name' => '媒体管理',
        'permissions' => [
            'media.view' => '查看媒体',
            'media.upload' => '上传文件',
            'media.edit' => '编辑媒体',
            'media.delete' => '删除媒体',
        ]
    ],
    'user' => [
        'name' => '用户管理',
        'permissions' => [
            'user.view' => '查看用户',
            'user.create' => '创建用户',
            'user.edit' => '编辑用户',
            'user.delete' => '删除用户',
            'user.assign_permissions' => '分配权限',
            'user.assign_admin' => '分配管理员角色',
        ]
    ],
    'system' => [
        'name' => '系统管理',
        'permissions' => [
            'setting.view' => '查看设置',
            'setting.edit' => '编辑设置',
            'backup.create' => '创建备份',
            'backup.restore' => '恢复备份',
            'log.view' => '查看日志',
            'cache.clear' => '清理缓存',
        ]
    ]
];

// 角色权限映射
$rolePermissions = [
    'subscriber' => [
        'article.view'
    ],
    'author' => [
        'article.view', 'article.create', 'article.edit_own', 'article.delete_own',
        'media.view', 'media.upload', 'category.view', 'tag.view'
    ],
    'editor' => [
        'article.view', 'article.create', 'article.edit', 'article.delete', 'article.publish',
        'media.view', 'media.upload', 'media.edit', 'media.delete',
        'category.view', 'category.create', 'category.edit', 'category.delete',
        'tag.view', 'tag.create', 'tag.edit', 'tag.delete'
    ],
    'admin' => [
        'article.view', 'article.create', 'article.edit', 'article.delete', 'article.publish',
        'media.view', 'media.upload', 'media.edit', 'media.delete',
        'category.view', 'category.create', 'category.edit', 'category.delete',
        'tag.view', 'tag.create', 'tag.edit', 'tag.delete',
        'user.view', 'user.create', 'user.edit', 'user.delete',
        'setting.view', 'setting.edit', 'log.view', 'cache.clear'
    ],
    'super_admin' => [
        'article.view', 'article.create', 'article.edit', 'article.delete', 'article.publish',
        'media.view', 'media.upload', 'media.edit', 'media.delete',
        'category.view', 'category.create', 'category.edit', 'category.delete',
        'tag.view', 'tag.create', 'tag.edit', 'tag.delete',
        'user.view', 'user.create', 'user.edit', 'user.delete', 'user.assign_permissions', 'user.assign_admin',
        'setting.view', 'setting.edit', 'backup.create', 'backup.restore', 'log.view', 'cache.clear'
    ]
];

include '../templates/admin_header.php';
?>

<div class="admin-container users-page">
    <main class="main-content">
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-user-shield"></i> 权限管理</h1>
                <p>管理用户 "<?php echo htmlspecialchars($user['username']); ?>" 的权限设置</p>
            </div>
            <div class="header-actions">
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回用户列表
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-card">
            <div class="card-header">
                <h3>用户信息</h3>
            </div>
            <div class="user-info-section">
                <div class="user-basic-info">
                    <div class="user-avatar">
                        <?php if ($user['avatar']): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="头像" class="avatar-img">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="role-badge role-<?php echo $user['role']; ?>">
                            <?php
                            $roleLabels = [
                                'super_admin' => '超级管理员',
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'author' => '作者',
                                'subscriber' => '订阅者'
                            ];
                            echo $roleLabels[$user['role']] ?? $user['role'];
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" class="permissions-form">
            <input type="hidden" name="action" value="update_permissions">
            
            <div class="content-card">
                <div class="card-header">
                    <h3>角色设置</h3>
                </div>
                <div class="role-selection">
                    <div class="form-group">
                        <label for="role">用户角色</label>
                        <select id="role" name="role" class="form-control" onchange="updatePermissionPreview()">
                            <option value="subscriber" <?php echo $user['role'] === 'subscriber' ? 'selected' : ''; ?>>订阅者</option>
                            <option value="author" <?php echo $user['role'] === 'author' ? 'selected' : ''; ?>>作者</option>
                            <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>编辑</option>
                            <?php if ($auth->hasPermission('user.assign_admin')): ?>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>管理员</option>
                                <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>超级管理员</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>权限详情</h3>
                </div>
                <div class="permissions-matrix">
                    <div class="permission-header">功能模块</div>
                    <div class="permission-header">订阅者</div>
                    <div class="permission-header">作者</div>
                    <div class="permission-header">编辑</div>
                    <div class="permission-header">管理员</div>
                    <div class="permission-header">超级管理员</div>

                    <?php foreach ($permissionDefinitions as $moduleKey => $module): ?>
                        <div class="permission-module"><?php echo $module['name']; ?></div>
                        <?php
                        $roles = ['subscriber', 'author', 'editor', 'admin', 'super_admin'];
                        foreach ($roles as $role) {
                            $hasPermissions = false;
                            $partialPermissions = false;
                            $modulePermissions = $module['permissions'];
                            $rolePerms = $rolePermissions[$role] ?? [];
                            
                            $count = 0;
                            foreach ($modulePermissions as $perm => $label) {
                                if (in_array($perm, $rolePerms)) {
                                    $count++;
                                }
                            }
                            
                            if ($count === count($modulePermissions)) {
                                $hasPermissions = true;
                            } elseif ($count > 0) {
                                $partialPermissions = true;
                            }
                            
                            $class = 'permission-denied';
                            $icon = '❌';
                            if ($hasPermissions) {
                                $class = 'permission-allowed';
                                $icon = '✅';
                            } elseif ($partialPermissions) {
                                $class = 'permission-partial';
                                $icon = '📝';
                            }
                            
                            echo "<div class='permission-cell {$class}'>{$icon}</div>";
                        }
                        ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="permission-legend">
                    <div class="legend-item">
                        <span class="legend-icon permission-allowed">✅</span>
                        <span>完全权限</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon permission-partial">📝</span>
                        <span>部分权限</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon permission-denied">❌</span>
                        <span>无权限</span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>当前用户权限列表</h3>
                </div>
                <div class="current-permissions" id="currentPermissions">
                    <!-- 权限列表将通过JavaScript动态加载 -->
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 保存权限设置
                </button>
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> 取消
                </a>
            </div>
        </form>
    </main>
</div>

<style>
.user-info-section {
    padding: 1.5rem;
}

.user-basic-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.user-details h4 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
}

.user-details p {
    margin: 0 0 0.5rem 0;
    color: #6c757d;
}

.role-selection {
    padding: 1.5rem;
}

.permissions-form .content-card {
    margin-bottom: 1.5rem;
}

.permission-legend {
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-icon {
    width: 20px;
    text-align: center;
}

.current-permissions {
    padding: 1.5rem;
}

.permission-group {
    margin-bottom: 1.5rem;
}

.permission-group h4 {
    color: #495057;
    margin-bottom: 0.75rem;
    font-size: 1rem;
}

.permission-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
}

.permission-item {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    border: 1px solid #bbdefb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.permission-item i {
    color: #1976d2;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    align-items: center;
    padding: 1.5rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
// 权限数据
const permissionDefinitions = <?php echo json_encode($permissionDefinitions); ?>;
const rolePermissions = <?php echo json_encode($rolePermissions); ?>;

// 更新权限预览
function updatePermissionPreview() {
    const role = document.getElementById('role').value;
    const permissions = rolePermissions[role] || [];
    
    const container = document.getElementById('currentPermissions');
    let html = '';
    
    for (const [moduleKey, module] of Object.entries(permissionDefinitions)) {
        const modulePermissions = [];
        
        for (const [perm, label] of Object.entries(module.permissions)) {
            if (permissions.includes(perm)) {
                modulePermissions.push(label);
            }
        }
        
        if (modulePermissions.length > 0) {
            html += `<div class="permission-group">`;
            html += `<h4><i class="fas fa-cog"></i> ${module.name}</h4>`;
            html += `<div class="permission-list">`;
            
            modulePermissions.forEach(label => {
                html += `<div class="permission-item">`;
                html += `<i class="fas fa-check"></i>`;
                html += `<span>${label}</span>`;
                html += `</div>`;
            });
            
            html += `</div></div>`;
        }
    }
    
    if (html === '') {
        html = '<p class="text-muted">该角色暂无特殊权限</p>';
    }
    
    container.innerHTML = html;
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    updatePermissionPreview();
});
</script>

<?php include '../templates/admin_footer.php'; ?>