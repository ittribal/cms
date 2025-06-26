<?php
// ==================== admin/includes/sidebar.php - 后台侧边栏 ====================
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar">
    <ul class="nav-menu">
        <li><a href="/admin/dashboard.php" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
            <span class="icon">📊</span> 控制台
        </a></li>
        
        <?php if ($auth->hasPermission('users.view')): ?>
        <li><a href="/admin/users/" class="<?= $current_dir === 'users' ? 'active' : '' ?>">
            <span class="icon">👥</span> 用户管理
        </a></li>
        <?php endif; ?>
        
        <?php if ($auth->hasPermission('content.view')): ?>
        <li><a href="/admin/content/articles.php" class="<?= ($current_dir === 'content' && in_array($current_page, ['articles', 'article_add', 'article_edit'])) ? 'active' : '' ?>">
            <span class="icon">📝</span> 文章管理
        </a></li>
        
        <li><a href="/admin/content/categories.php" class="<?= ($current_dir === 'content' && $current_page === 'categories') ? 'active' : '' ?>">
            <span class="icon">📂</span> 分类管理
        </a></li>
        
        <li><a href="/admin/content/media.php" class="<?= ($current_dir === 'content' && $current_page === 'media') ? 'active' : '' ?>">
            <span class="icon">🖼️</span> 媒体管理
        </a></li>
        <?php endif; ?>
        
        <?php if ($auth->hasPermission('system.settings')): ?>
        <li><a href="/admin/system/settings.php" class="<?= ($current_dir === 'system' && $current_page === 'settings') ? 'active' : '' ?>">
            <span class="icon">⚙️</span> 系统设置
        </a></li>
        <?php endif; ?>
        
        <?php if ($auth->hasPermission('system.logs')): ?>
        <li><a href="/admin/system/logs.php" class="<?= ($current_dir === 'system' && $current_page === 'logs') ? 'active' : '' ?>">
            <span class="icon">📋</span> 系统日志
        </a></li>
        <?php endif; ?>
        
        <?php if ($auth->hasPermission('system.backup')): ?>
        <li><a href="/admin/system/backup.php" class="<?= ($current_dir === 'system' && $current_page === 'backup') ? 'active' : '' ?>">
            <span class="icon">💾</span> 数据备份
        </a></li>
        <?php endif; ?>
        
        <li><a href="/" target="_blank">
            <span class="icon">🌐</span> 访问前台
        </a></li>
    </ul>
</aside>