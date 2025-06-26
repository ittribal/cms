<?php
// public/includes/header.php - 页面头部组件
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta标签 -->
    <title><?php echo htmlspecialchars($pageTitle ?? '现代化CMS网站'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? '基于PHP+MySQL开发的现代化内容管理系统'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords ?? 'CMS,内容管理,博客,文章'); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($settings['site_author'] ?? 'CMS Team'); ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle ?? '现代化CMS网站'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription ?? '基于PHP+MySQL开发的现代化内容管理系统'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars(getCurrentUrl()); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($pageImage ?? SITE_URL . '/assets/images/og-default.jpg'); ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle ?? '现代化CMS网站'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription ?? '基于PHP+MySQL开发的现代化内容管理系统'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($pageImage ?? SITE_URL . '/assets/images/og-default.jpg'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/assets/images/apple-touch-icon.png">
    
    <!-- 预连接到外部资源 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- 字体 -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome图标 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- 主样式表 -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- RSS订阅 -->
    <link rel="alternate" type="application/rss+xml" title="<?php echo htmlspecialchars($settings['site_title'] ?? 'CMS网站'); ?> RSS" href="<?php echo SITE_URL; ?>/rss.php">
    
    <!-- PWA支持 -->
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#667eea">
    
    <!-- 性能优化 -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <!-- 结构化数据 -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Website",
        "name": "<?php echo htmlspecialchars($settings['site_title'] ?? 'CMS网站'); ?>",
        "description": "<?php echo htmlspecialchars($settings['site_description'] ?? '现代化内容管理系统'); ?>",
        "url": "<?php echo SITE_URL; ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo SITE_URL; ?>/search.php?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <?php if (isset($additionalHead)): ?>
        <?php echo $additionalHead; ?>
    <?php endif; ?>
</head>
<body>
    <!-- 页面加载器 -->
    <div id="pageLoader" class="page-loader">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <div class="loader-text">加载中...</div>
        </div>
    </div>

    <!-- 返回顶部按钮 -->
    <button id="backToTop" class="back-to-top" title="返回顶部">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- 网站头部 -->
    <header class="site-header" id="siteHeader">
        <div class="container">
            <div class="header-container">
                <!-- 网站Logo -->
                <div class="site-branding">
                    <a href="<?php echo SITE_URL; ?>/" class="site-logo">
                        <div class="logo-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <span class="logo-text">
                            <?php echo htmlspecialchars($settings['site_title'] ?? 'CMS'); ?>
                        </span>
                    </a>
                </div>

                <!-- 主导航 -->
                <nav class="main-nav" role="navigation" aria-label="主导航">
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/" class="nav-link <?php echo isCurrentPage('/') ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i>
                                <span>首页</span>
                            </a>
                        </li>
                        
                        <?php
                        // 获取主要分类作为导航菜单
                        $navCategories = $db->fetchAll("
                            SELECT c.*, COUNT(a.id) as article_count
                            FROM categories c 
                            LEFT JOIN articles a ON c.id = a.category_id AND a.status = 'published'
                            WHERE c.status = 'active' AND c.parent_id IS NULL
                            GROUP BY c.id
                            ORDER BY c.sort_order ASC
                            LIMIT 6
                        ");
                        
                        foreach ($navCategories as $category):
                        ?>
                            <li class="nav-item">
                                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo urlencode($category['slug']); ?>" 
                                   class="nav-link <?php echo isCurrentPage('/category.php', ['slug' => $category['slug']]) ? 'active' : ''; ?>">
                                    <i class="fas fa-folder"></i>
                                    <span><?php echo htmlspecialchars($category['name']); ?></span>
                                    <span class="nav-count"><?php echo $category['article_count']; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/articles.php" class="nav-link <?php echo isCurrentPage('/articles.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>所有文章</span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a href="<?php echo SITE_URL; ?>/about.php" class="nav-link <?php echo isCurrentPage('/about.php') ? 'active' : ''; ?>">
                                <i class="fas fa-info-circle"></i>
                                <span>关于我们</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- 头部工具栏 -->
                <div class="header-tools">
                    <!-- 搜索按钮 -->
                    <button class="search-toggle" onclick="toggleSearch()" title="搜索" aria-label="打开搜索">
                        <i class="fas fa-search"></i>
                    </button>
                    
                    <!-- 主题切换 -->
                    <button class="theme-toggle" onclick="toggleTheme()" title="切换主题" aria-label="切换主题">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    
                    <!-- RSS订阅 -->
                    <a href="<?php echo SITE_URL; ?>/rss.php" class="rss-link" title="RSS订阅" target="_blank">
                        <i class="fas fa-rss"></i>
                    </a>
                    
                    <!-- 移动端菜单按钮 -->
                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" title="菜单" aria-label="打开菜单">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 搜索覆盖层 -->
        <div class="search-overlay" id="searchOverlay">
            <div class="search-overlay-content">
                <div class="search-header">
                    <h2 class="search-overlay-title">搜索内容</h2>
                    <button class="search-close" onclick="toggleSearch()" aria-label="关闭搜索">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form class="search-overlay-form" action="<?php echo SITE_URL; ?>/search.php" method="GET">
                    <div class="search-overlay-input-group">
                        <input type="text" name="q" class="search-overlay-input" 
                               placeholder="输入关键词搜索..." autocomplete="off" id="searchInput">
                        <button type="submit" class="search-overlay-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <!-- 搜索建议 -->
                <div class="search-suggestions" id="searchSuggestions">
                    <div class="suggestions-section">
                        <h3>热门搜索</h3>
                        <div class="suggestion-tags">
                            <?php
                            $popularSearches = ['技术', '设计', '生活', '编程', '教程'];
                            foreach ($popularSearches as $term):
                            ?>
                                <a href="<?php echo SITE_URL; ?>/search.php?q=<?php echo urlencode($term); ?>" class="suggestion-tag">
                                    <?php echo htmlspecialchars($term); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="suggestions-section">
                        <h3>最新文章</h3>
                        <div class="suggestion-articles" id="recentArticles">
                            <?php
                            $recentArticles = $db->fetchAll("
                                SELECT title, slug, created_at
                                FROM articles 
                                WHERE status = 'published'
                                ORDER BY created_at DESC 
                                LIMIT 5
                            ");
                            
                            foreach ($recentArticles as $article):
                            ?>
                                <a href="<?php echo SITE_URL; ?>/article.php?slug=<?php echo urlencode($article['slug']); ?>" 
                                   class="suggestion-article">
                                    <div class="suggestion-article-content">
                                        <h4><?php echo htmlspecialchars($article['title']); ?></h4>
                                        <span class="suggestion-article-date">
                                            <?php echo formatDate($article['created_at'], 'short'); ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 移动端菜单 -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-content">
                <div class="mobile-menu-header">
                    <div class="mobile-logo">
                        <div class="logo-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <span><?php echo htmlspecialchars($settings['site_title'] ?? 'CMS'); ?></span>
                    </div>
                    <button class="mobile-menu-close" onclick="toggleMobileMenu()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <nav class="mobile-nav">
                    <ul class="mobile-nav-menu">
                        <li class="mobile-nav-item">
                            <a href="<?php echo SITE_URL; ?>/" class="mobile-nav-link">
                                <i class="fas fa-home"></i>
                                <span>首页</span>
                            </a>
                        </li>
                        
                        <?php foreach ($navCategories as $category): ?>
                            <li class="mobile-nav-item">
                                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo urlencode($category['slug']); ?>" 
                                   class="mobile-nav-link">
                                    <i class="fas fa-folder"></i>
                                    <span><?php echo htmlspecialchars($category['name']); ?></span>
                                    <span class="mobile-nav-count"><?php echo $category['article_count']; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        
                        <li class="mobile-nav-item">
                            <a href="<?php echo SITE_URL; ?>/articles.php" class="mobile-nav-link">
                                <i class="fas fa-list"></i>
                                <span>所有文章</span>
                            </a>
                        </li>
                        
                        <li class="mobile-nav-item">
                            <a href="<?php echo SITE_URL; ?>/about.php" class="mobile-nav-link">
                                <i class="fas fa-info-circle"></i>
                                <span>关于我们</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <div class="mobile-menu-footer">
                    <div class="mobile-actions">
                        <button class="mobile-action-btn" onclick="toggleTheme()">
                            <i class="fas fa-moon" id="mobileThemeIcon"></i>
                            <span>切换主题</span>
                        </button>
                        <a href="<?php echo SITE_URL; ?>/rss.php" class="mobile-action-btn" target="_blank">
                            <i class="fas fa-rss"></i>
                            <span>RSS订阅</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 页面内容开始 -->
    <div class="page-wrapper">

<style>
/* 页面加载器 */
.page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--bg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    transition: opacity 0.5s ease, visibility 0.5s ease;
}

.page-loader.hidden {
    opacity: 0;
    visibility: hidden;
}

.loader-content {
    text-align: center;
}

.loader-spinner {
    width: 50px;
    height: 50px;
    margin: 0 auto 1rem;
    border: 3px solid var(--border-color);
    border-top: 3px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loader-text {
    color: var(--text-secondary);
    font-weight: 500;
}

/* 返回顶部按钮 */
.back-to-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-normal);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: var(--shadow-lg);
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
}

/* 头部样式增强 */
.nav-count {
    background: var(--primary-color);
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    margin-left: 0.5rem;
}

.rss-link {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: all var(--transition-normal);
}

.rss-link:hover {
    background: var(--bg-tertiary);
    color: #ff6600;
}

/* 搜索覆盖层 */
.search-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-normal);
}

.search-overlay.active {
    opacity: 1;
    visibility: visible;
}

.search-overlay-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 600px;
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: var(--space-xl);
    box-shadow: var(--shadow-xl);
    max-height: 80vh;
    overflow-y: auto;
}

.search-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-xl);
}

.search-overlay-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.search-close {
    width: 40px;
    height: 40px;
    border: none;
    background: var(--bg-tertiary);
    color: var(--text-secondary);
    border-radius: 50%;
    cursor: pointer;
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-close:hover {
    background: var(--primary-color);
    color: white;
}

.search-overlay-input-group {
    position: relative;
    margin-bottom: var(--space-xl);
}

.search-overlay-input {
    width: 100%;
    padding: var(--space-lg);
    padding-right: 60px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 1.1rem;
    font-family: inherit;
    background: var(--bg-primary);
    color: var(--text-primary);
    transition: all var(--transition-normal);
}

.search-overlay-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-overlay-btn {
    position: absolute;
    right: 5px;
    top: 5px;
    bottom: 5px;
    width: 50px;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-suggestions {
    display: grid;
    gap: var(--space-xl);
}

.suggestions-section h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-md);
}

.suggestion-tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-sm);
}

.suggestion-tag {
    padding: var(--space-sm) var(--space-md);
    background: var(--bg-tertiary);
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.9rem;
    transition: all var(--transition-normal);
}

.suggestion-tag:hover {
    background: var(--primary-color);
    color: white;
}

.suggestion-articles {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.suggestion-article {
    display: flex;
    align-items: center;
    padding: var(--space-md);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    text-decoration: none;
    transition: all var(--transition-normal);
}

.suggestion-article:hover {
    background: var(--bg-tertiary);
    transform: translateX(5px);
}

.suggestion-article-content h4 {
    color: var(--text-primary);
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.suggestion-article-date {
    color: var(--text-muted);
    font-size: 0.8rem;
}

/* 移动端菜单 */
.mobile-menu {
    position: fixed;
    top: 0;
    right: -100%;
    width: 320px;
    height: 100%;
    background: var(--bg-primary);
    box-shadow: var(--shadow-xl);
    z-index: 9998;
    transition: right var(--transition-normal);
    border-left: 1px solid var(--border-color);
}

.mobile-menu.active {
    right: 0;
}

.mobile-menu-content {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.mobile-menu-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-lg);
    border-bottom: 1px solid var(--border-color);
}

.mobile-logo {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    font-weight: 600;
    color: var(--text-primary);
}

.mobile-menu-close {
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    color: var(--text-secondary);
    border-radius: 50%;
    cursor: pointer;
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-menu-close:hover {
    background: var(--bg-tertiary);
}

.mobile-nav {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-md) 0;
}

.mobile-nav-menu {
    list-style: none;
}

.mobile-nav-item {
    margin-bottom: var(--space-xs);
}

.mobile-nav-link {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md) var(--space-lg);
    color: var(--text-secondary);
    text-decoration: none;
    transition: all var(--transition-normal);
}

.mobile-nav-link:hover {
    background: var(--bg-secondary);
    color: var(--primary-color);
}

.mobile-nav-count {
    margin-left: auto;
    background: var(--primary-color);
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
}

.mobile-menu-footer {
    padding: var(--space-lg);
    border-top: 1px solid var(--border-color);
}

.mobile-actions {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.mobile-action-btn {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md);
    background: var(--bg-secondary);
    color: var(--text-secondary);
    text-decoration: none;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all var(--transition-normal);
    font-family: inherit;
    font-size: 1rem;
}

.mobile-action-btn:hover {
    background: var(--primary-color);
    color: white;
}

/* 移动端优化 */
@media (max-width: 768px) {
    .nav-menu {
        display: none;
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .search-overlay-content {
        width: 95%;
        padding: var(--space-lg);
    }
    
    .mobile-menu {
        width: 280px;
    }
}

@media (max-width: 480px) {
    .back-to-top {
        bottom: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
    }
    
    .search-overlay-input {
        font-size: 1rem;
        padding: var(--space-md);
        padding-right: 55px;
    }
    
    .search-overlay-btn {
        width: 45px;
    }
}
</style>

<script>
// 页面加载完成处理
window.addEventListener('load', function() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.classList.add('hidden');
        setTimeout(() => loader.remove(), 500);
    }
});

// 头部滚动效果
let lastScrollY = window.scrollY;
const header = document.getElementById('siteHeader');

window.addEventListener('scroll', function() {
    const currentScrollY = window.scrollY;
    
    if (currentScrollY > 100) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove