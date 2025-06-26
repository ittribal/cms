<?php // <-- 确保这是文件的第一行第一列，前面没有任何字符或空格
// 这个PHP块必须是文件的第一个内容，前面不能有任何输出。
// 它依赖于在主入口页面（如 dashboard.php）中引入的 includes/config.php 和 Auth 类。

// 检查 $auth 对象是否已正确实例化并登录
if (!isset($auth) || !$auth->isLoggedIn()) {
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

// 获取当前用户信息 (如果已登录)
$currentUser = $auth->isLoggedIn() ? $auth->getCurrentUser() : null;
// 获取当前页面文件名（不带 .php 后缀）
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
// 获取当前文件所在的二级目录名 (例如 'system', 'content', 'users', 'tags')
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// 定义页面标题（如果主文件没有定义）
$pageTitle = $pageTitle ?? 'CMS管理后台';

// 获取角色标签映射，用于显示用户角色名称
$roleLabels = Auth::$roleLabels ?? [];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($pageTitle) ?> | <?= esc_html(SITE_TITLE) ?></title>
    
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/admin.css?v=<?= time() ?>"> 
    <?php /*
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/articles.css?v=<?= time() ?>"> 
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/categories.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/comments.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/media.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/profile.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/settings.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/users.css?v=<?= time() ?>">
    */ ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/public/assets/favicon.ico">
</head>
<body>

    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-cogs"></i> CMS管理</h2>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/system/dashboard.php" class="nav-link <?= ($currentDir === 'system' && $currentPage === 'dashboard') ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>仪表盘</span>
                    </a>
                </div>
                
                <?php if ($auth->hasPermission('content.view')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/content/articles.php" class="nav-link <?= ($currentDir === 'content' && in_array($currentPage, ['articles', 'article_form'])) ? 'active' : '' ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>文章管理</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('categories.view')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/content/categories.php" class="nav-link <?= ($currentDir === 'content' && $currentPage === 'categories') ? 'active' : '' ?>">
                        <i class="fas fa-folder"></i>
                        <span>分类管理</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('tags.view')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/tags/index.php" class="nav-link <?= ($currentDir === 'tags' && $currentPage === 'index') ? 'active' : '' ?>">
                        <i class="fas fa-tags"></i>
                        <span>标签管理</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('media.view')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/content/media.php" class="nav-link <?= ($currentDir === 'content' && $currentPage === 'media') ? 'active' : '' ?>">
                        <i class="fas fa-images"></i>
                        <span>媒体库</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('users.view')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/users/index.php" class="nav-link <?= ($currentDir === 'users' && in_array($currentPage, ['index', 'user_form', 'permissions'])) ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>用户管理</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('comments.view')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/comments.php" class="nav-link <?= ($currentPage === 'comments') ? 'active' : '' ?>">
                        <i class="fas fa-comments"></i>
                        <span>评论管理</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('system.settings')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/system/settings.php" class="nav-link <?= ($currentDir === 'system' && $currentPage === 'settings') ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i>
                        <span>系统设置</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('system.logs')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/system/logs.php" class="nav-link <?= ($currentDir === 'system' && $currentPage === 'logs') ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>操作日志</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('system.backup')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/system/backup.php" class="nav-link <?= ($currentDir === 'system' && $currentPage === 'backup') ? 'active' : '' ?>">
                        <i class="fas fa-database"></i>
                        <span>数据备份</span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($auth->hasPermission('emails.view')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/system/emails.php" class="nav-link <?= ($currentDir === 'system' && $currentPage === 'emails') ? 'active' : '' ?>">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>邮件管理</span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($auth->hasPermission('system.seo')): ?>
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/system/seo.php" class="nav-link <?= ($currentDir === 'system' && $currentPage === 'seo') ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>SEO优化</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/profile.php" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                        <i class="fas fa-user-circle"></i>
                        <span>个人设置</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/public/index.php" class="nav-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>访问前台</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="<?= SITE_URL ?>/admin/logout.php" class="nav-link" onclick="return confirm('确定要退出登录吗？')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>退出登录</span>
                    </a>
                </div>
            </nav>
        </aside>

        <div class="main-wrapper" style="flex: 1; display: flex; flex-direction: column;">
            <header class="top-navbar">
                <div class="navbar-brand">
                    <i class="fas fa-home"></i>
                    <?= esc_html(SITE_TITLE) ?>
                </div>
                
                <div class="navbar-nav">
                    <div class="nav-user">
                        <div class="user-avatar">
                            <?= $currentUser ? strtoupper(substr($currentUser['username'], 0, 2)) : '?' ?>
                        </div>
                        <span>
                            欢迎，<?= $currentUser ? htmlspecialchars($currentUser['username']) : '访客' ?>
                            <small class="text-muted">
                                (<?php 
                                    if ($currentUser) {
                                        $roleLabels = Auth::$roleLabels; 
                                        echo $roleLabels[$currentUser['role_name']] ?? $currentUser['role_name']; 
                                    } else {
                                        echo '未登录';
                                    }
                                ?>)
                            </small>
                        </span>
                    </div>
                    
                    <div class="navbar-actions">
                        <?php if ($currentUser): ?>
                        <a href="<?= SITE_URL ?>/admin/profile.php" class="btn btn-sm btn-outline" title="个人设置">
                            <i class="fas fa-user-cog"></i>
                        </a>
                        <?php endif; ?>
                        <a href="<?= SITE_URL ?>/public/index.php" class="btn btn-sm btn-outline" target="_blank" title="查看网站">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-sm btn-secondary" 
                           onclick="return confirm('确定要退出登录吗？')" title="退出登录">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </header>

            <?php 
            $flash_message = get_flash_message();
            if ($flash_message): 
            ?>
                <div class="alert alert-<?= $flash_message['type'] ?>">
                    <i class="fas fa-<?= $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($flash_message['message']) ?>
                </div>
            <?php endif; ?>