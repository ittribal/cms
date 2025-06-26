<?php
// ==================== admin/includes/header.php - 后台头部 ====================
require_once '../config/database.php';
require_once '../includes/auth.php';

$db = new Database();
$auth = new Auth($db);
$auth->requireLogin();

$current_user = $auth->getCurrentUser();
?>
<header class="admin-header">
    <div class="header-content">
        <div class="logo">CMS 管理系统</div>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($current_user['username'], 0, 1)) ?></div>
            <div>
                <div><?= htmlspecialchars($current_user['username']) ?></div>
                <div style="font-size: 12px; opacity: 0.8;"><?= htmlspecialchars($current_user['role_name']) ?></div>
            </div>
            <a href="logout.php" class="logout-btn">退出</a>
        </div>
    </div>
</header>