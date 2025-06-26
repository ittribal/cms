<?php
// templates/admin_header.php - 后台头部导航模板
if (!isset($auth)) {
    require_once '../includes/config.php';
    require_once '../includes/Database.php';
    require_once '../includes/Auth.php';
    $auth = new Auth();
}

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '管理后台'; ?> - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft YaHei', 'PingFang SC', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            text-decoration: none;
            color: white;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .role-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .header-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .header-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .header-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }
        
        .main-nav {
            padding: 0 2rem;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-bottom-color: rgba(255,255,255,0.5);
        }
        
        .nav-link.active {
            border-bottom-color: white;
        }
        
        .nav-link i {
            font-size: 1.1rem;
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-radius: 8px;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .dropdown-item:hover {
            background: #f8f9fa;
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-item i {
            width: 20px;
            color: #666;
        }
        
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .header-top {
                padding: 1rem;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .main-nav {
                display: none;
                padding: 0;
            }
            
            .main-nav.show {
                display: block;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .nav-link {
                padding: 1rem 2rem;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                border-left: none;
            }
            
            .dropdown-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                background: rgba(255,255,255,0.1);
                margin-left: 2rem;
            }
            
            .dropdown-item {
                color: rgba(255,255,255,0.8);
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
        }
        
        .admin-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }
        
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding: 2rem 0;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-item {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
        }
        
        .sidebar-link:hover,
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), transparent);
            color: #667eea;
            border-right-color: #667eea;
        }
        
        .sidebar-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            background: #f5f7fa;
        }
        
        .page-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }
        
        .breadcrumb {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        
        .page-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #219a52; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #95a5a6; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-info h3 {
            font-size: 1.8rem;
            margin: 0;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #17a2b8;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-top">
            <a href="index.php" class="logo">
                <i class="fas fa-home"></i>
                <?php echo SITE_TITLE; ?> - 管理后台
            </a>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <div>👋 欢迎，<?php echo htmlspecialchars($currentUser['real_name'] ?? $currentUser['username']); ?></div>
                        <div class="role-badge"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                    </div>
                </div>
                
                <div class="header-actions">
                    <a href="../public/index.php" target="_blank" class="header-btn">
                        <i class="fas fa-external-link-alt"></i> 查看网站
                    </a>
                    <a href="logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> 退出登录
                    </a>
                </div>
            </div>
            
            <button class="mobile-toggle" onclick="toggleMobileNav()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <nav class="main-nav" id="mainNav">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>仪表盘</span>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link <?php echo in_array($currentPage, ['articles', 'article_add', 'article_edit']) ? 'active' : ''; ?>">
                        <i class="fas fa-edit"></i>
                        <span>内容管理</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="articles.php" class="dropdown-item">
                            <i class="fas fa-list"></i> 文章列表
                        </a>
                        <a href="articles.php?action=add" class="dropdown-item">
                            <i class="fas fa-plus"></i> 添加文章
                        </a>
                        <a href="categories.php" class="dropdown-item">
                            <i class="fas fa-folder"></i> 分类管理
                        </a>
                        <a href="tags.php" class="dropdown-item">
                            <i class="fas fa-tags"></i> 标签管理
                        </a>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>用户管理</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="comments.php" class="nav-link <?php echo $currentPage === 'comments' ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i>
                        <span>评论管理</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="media.php" class="nav-link <?php echo $currentPage === 'media' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i>
                        <span>媒体管理</span>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link <?php echo in_array($currentPage, ['settings', 'emails', 'seo']) ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i>
                        <span>系统管理</span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i> 系统设置
                        </a>
                        <a href="emails.php" class="dropdown-item">
                            <i class="fas fa-envelope"></i> 邮件管理
                        </a>
                        <a href="seo.php" class="dropdown-item">
                            <i class="fas fa-search"></i> SEO工具
                        </a>
                        <a href="backups.php" class="dropdown-item">
                            <i class="fas fa-database"></i> 数据备份
                        </a>
                        <a href="logs.php" class="dropdown-item">
                            <i class="fas fa-list-alt"></i> 系统日志
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <script>
        function toggleMobileNav() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('show');
        }
        
        // 关闭移动端导航
        document.addEventListener('click', function(e) {
            const nav = document.getElementById('mainNav');
            const toggle = document.querySelector('.mobile-toggle');
            
            if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('show');
            }
        });
    </script>
</body>
</html>