<?php
// ==================== admin/users/index.php - 用户管理 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('users.view');

// 分页处理
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$search = sanitize_input($_GET['search'] ?? '');

$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = 'WHERE username LIKE ? OR email LIKE ?';
    $params = ["%$search%", "%$search%"];
}

$total = $db->fetchOne("SELECT COUNT(*) as count FROM admin_users $where_clause", $params)['count'];
$pagination = paginate($total, $page, $per_page);

$users = $db->fetchAll(
    "SELECT u.*, r.name as role_name 
     FROM admin_users u 
     JOIN admin_roles r ON u.role_id = r.id 
     $where_clause
     ORDER BY u.created_at DESC 
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">用户管理</h1>
                <div class="page-actions">
                    <?php if ($auth->hasPermission('users.create')): ?>
                        <a href="add.php" class="btn btn-primary">
                            <span class="icon">➕</span> 添加用户
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="card-header">
                    <div class="search-box">
                        <form method="GET" action="">
                            <input type="text" name="search" placeholder="搜索用户名或邮箱..." 
                                   value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-secondary">搜索</button>
                            <?php if ($search): ?>
                                <a href="index.php" class="btn btn-outline">清除</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>角色</th>
                                <th>状态</th>
                                <th>最后登录</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($user['username']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="role-badge role-<?= $user['role_id'] ?>">
                                            <?= htmlspecialchars($user['role_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $user['status'] ?>">
                                            <?= $user['status'] === 'active' ? '正常' : '禁用' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $user['last_login'] ? time_ago($user['last_login']) : '从未登录' ?>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($auth->hasPermission('users.edit')): ?>
                                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline">编辑</a>
                                            <?php endif; ?>
                                            <?php if ($auth->hasPermission('users.delete') && $user['id'] != $_SESSION['user_id']): ?>
                                                <a href="delete.php?id=<?= $user['id'] ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('确定要删除此用户吗？')">删除</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?page=<?= $pagination['prev_page'] ?>&search=<?= urlencode($search) ?>">上一页</a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            第 <?= $pagination['current_page'] ?> 页，共 <?= $pagination['total_pages'] ?> 页
                        </span>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?page=<?= $pagination['next_page'] ?>&search=<?= urlencode($search) ?>">下一页</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>