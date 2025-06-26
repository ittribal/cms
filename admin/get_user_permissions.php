<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = Database::getInstance();

// 检查登录和权限
if (!$auth->isLoggedIn() || !$auth->hasPermission('user.view')) {
    echo json_encode(['success' => false, 'message' => '没有权限']);
    exit;
}

$userId = $_GET['id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => '用户ID无效']);
    exit;
}

try {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => '用户不存在']);
        exit;
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
    
    $userPermissions = $rolePermissions[$user['role']] ?? [];
    
    // 角色标签
    $roleLabels = [
        'super_admin' => '超级管理员',
        'admin' => '管理员',
        'editor' => '编辑',
        'author' => '作者',
        'subscriber' => '订阅者'
    ];
    
    // 生成HTML
    $html = '<div class="user-permission-info">';
    
    // 用户基本信息
    $html .= '<div class="permission-user-info">';
    $html .= '<div class="user-header">';
    $html .= '<div class="user-avatar-small">';
    if ($user['avatar']) {
        $html .= '<img src="' . htmlspecialchars($user['avatar']) . '" alt="头像">';
    } else {
        $html .= '<div class="avatar-placeholder-small">' . strtoupper(substr($user['username'], 0, 2)) . '</div>';
    }
    $html .= '</div>';
    $html .= '<div class="user-details-small">';
    $html .= '<h4>' . htmlspecialchars($user['username']) . '</h4>';
    $html .= '<p>' . htmlspecialchars($user['email']) . '</p>';
    $html .= '<span class="role-badge role-' . $user['role'] . '">' . ($roleLabels[$user['role']] ?? $user['role']) . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // 权限列表
    $html .= '<div class="permission-groups">';
    
    foreach ($permissionDefinitions as $moduleKey => $module) {
        $modulePermissions = [];
        
        foreach ($module['permissions'] as $perm => $label) {
            if (in_array($perm, $userPermissions)) {
                $modulePermissions[] = $label;
            }
        }
        
        if (!empty($modulePermissions)) {
            $html .= '<div class="permission-group">';
            $html .= '<h4><i class="fas fa-cog"></i> ' . $module['name'] . '</h4>';
            $html .= '<div class="permission-list">';
            
            foreach ($modulePermissions as $label) {
                $html .= '<div class="permission-item">';
                $html .= '<i class="fas fa-check text-success"></i>';
                $html .= '<span>' . $label . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
    }
    
    if (empty(array_filter($permissionDefinitions, function($module) use ($userPermissions) {
        return !empty(array_intersect(array_keys($module['permissions']), $userPermissions));
    }))) {
        $html .= '<div class="no-permissions">';
        $html .= '<i class="fas fa-info-circle"></i>';
        $html .= '<p>该用户角色暂无特殊权限</p>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // 权限管理链接
    if ($auth->hasPermission('user.assign_permissions')) {
        $html .= '<div class="permission-actions">';
        $html .= '<a href="user_permissions.php?id=' . $user['id'] . '" class="btn btn-primary">';
        $html .= '<i class="fas fa-edit"></i> 管理权限';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // 添加样式
    $html .= '<style>
    .user-permission-info {
        max-width: 500px;
    }
    
    .permission-user-info {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .user-header {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .user-avatar-small {
        width: 50px;
        height: 50px;
    }
    
    .user-avatar-small img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .avatar-placeholder-small {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1rem;
    }
    
    .user-details-small h4 {
        margin: 0 0 0.25rem 0;
        color: #2c3e50;
    }
    
    .user-details-small p {
        margin: 0 0 0.5rem 0;
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .permission-groups {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .permission-group {
        margin-bottom: 1.5rem;
    }
    
    .permission-group h4 {
        color: #495057;
        margin-bottom: 0.75rem;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .permission-list {
        display: grid;
        gap: 0.5rem;
    }
    
    .permission-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: #e3f2fd;
        border-radius: 6px;
        font-size: 0.9rem;
    }
    
    .permission-item i {
        color: #27ae60;
        font-size: 0.8rem;
    }
    
    .no-permissions {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }
    
    .no-permissions i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        opacity: 0.5;
    }
    
    .permission-actions {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
        text-align: center;
    }
    
    .role-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
        text-align: center;
        display: inline-block;
    }
    
    .role-super_admin {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ffcdd2;
    }
    
    .role-admin {
        background: #fff3e0;
        color: #ef6c00;
        border: 1px solid #ffcc02;
    }
    
    .role-editor {
        background: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
    }
    
    .role-author {
        background: #e3f2fd;
        color: #1976d2;
        border: 1px solid #bbdefb;
    }
    
    .role-subscriber {
        background: #f3e5f5;
        color: #7b1fa2;
        border: 1px solid #e1bee7;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        border-color: #2980b9;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #2980b9, #21618c);
        color: white;
        transform: translateY(-1px);
    }
    </style>';
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'user' => $user,
        'permissions' => $userPermissions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '获取权限信息失败：' . $e->getMessage()
    ]);
}
?>