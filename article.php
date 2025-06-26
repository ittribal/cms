<?php
// ==================== article.php - 前台文章详情页 ====================
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();

// 获取文章slug
$slug = sanitize_input($_GET['slug'] ?? '');
if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// 获取文章详情
$article = $db->fetchOne(
    "SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name
     FROM articles a 
     LEFT JOIN categories c ON a.category_id = c.id 
     LEFT JOIN admin_users u ON a.author_id = u.id 
     WHERE a.slug = ? AND a.status = 'published'",
    [$slug]
);

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// 增加浏览量
$db->query("UPDATE articles SET views = views + 1 WHERE id = ?", [$article['id']]);

// 获取相关文章
$related_articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name 
     FROM articles a 
     LEFT JOIN categories c ON a.category_id = c.id 
     WHERE a.id != ? AND a.category_id = ? AND a.status = 'published' 
     ORDER BY a.published_at DESC 
     LIMIT 4",
    [$article['id'], $article['category_id']]
);

// 获取网站设置
$site_settings = [];
$settings_query = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
foreach ($settings_query as $setting) {
    $site_settings[$setting['setting_key']] = $setting['setting_value'];
}

$page_title = $article['meta_title'] ?: $article['title'];
$page_description = $article['meta_description'] ?: $article['excerpt'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($site_settings['site_name'] ?? '网站技术指南') ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($article['category_name'] ?? '') ?>, 技术文档, 编程教程">
    <meta name="author" content="<?= htmlspecialchars($article['author_name']) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
    <?php if ($article['featured_image']): ?>
        <meta property="og:image" content="<?= htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/' . $article['featured_image']) ?>">
    <?php endif; ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.7;
            color: #333;
            background: #fafbfc;
        }
        
        /* 头部样式 */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        
        .nav-menu a:hover {
            opacity: 0.8;
        }
        
        /* 面包屑导航 */
        .breadcrumb {
            background: white;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .breadcrumb-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .breadcrumb-list {
            display: flex;
            list-style: none;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .breadcrumb-list li:not(:last-child)::after {
            content: '›';
            margin: 0 0.5rem;
            color: #64748b;
        }
        
        .breadcrumb-list a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb-list a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-list .current {
            color: #64748b;
        }
        
        /* 主要内容 */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 3rem;
        }
        
        .article-content {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .article-header {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .article-category {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1rem;
            text-decoration: none;
        }
        
        .article-category:hover {
            background: #c7d2fe;
        }
        
        .article-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1e293b;
            line-height: 1.3;
        }
        
        .article-meta {
            display: flex;
            gap: 2rem;
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .article-featured-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .article-excerpt {
            font-size: 1.1rem;
            color: #475569;
            font-style: italic;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-left: 4px solid #667eea;
            border-radius: 0 6px 6px 0;
        }
        
        .article-body {
            font-size: 1.05rem;
            line-height: 1.8;
            color: #374151;
        }
        
        .article-body h1,
        .article-body h2,
        .article-body h3,
        .article-body h4 {
            margin: 2rem 0 1rem 0;
            color: #1e293b;
        }
        
        .article-body h2 {
            font-size: 1.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .article-body h3 {
            font-size: 1.5rem;
        }
        
        .article-body p {
            margin-bottom: 1.5rem;
        }
        
        .article-body ul,
        .article-body ol {
            margin: 1rem 0 1.5rem 2rem;
        }
        
        .article-body li {
            margin-bottom: 0.5rem;
        }
        
        .article-body blockquote {
            margin: 1.5rem 0;
            padding: 1rem 1.5rem;
            background: #f1f5f9;
            border-left: 4px solid #64748b;
            border-radius: 0 6px 6px 0;
            font-style: italic;
        }
        
        .article-body pre {
            background: #1e293b;
            color: #f1f5f9;
            padding: 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 1.5rem 0;
            font-size: 0.9rem;
        }
        
        .article-body code {
            background: #f1f5f9;
            color: #e11d48;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .article-body pre code {
            background: none;
            color: inherit;
            padding: 0;
        }
        
        .article-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .article-body th,
        .article-body td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .article-body th {
            background: #f8fafc;
            font-weight: 600;
        }
        
        .article-body img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin: 1rem 0;
        }
        
        /* 侧边栏 */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .sidebar-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1e293b;
        }
        
        .toc {
            list-style: none;
        }
        
        .toc a {
            display: block;
            padding: 0.5rem 0;
            color: #64748b;
            text-decoration: none;
            border-bottom: 1px solid #f1f5f9;
            transition: color 0.3s ease;
        }
        
        .toc a:hover {
            color: #667eea;
        }
        
        .related-article {
            display: block;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }
        
        .related-article:hover {
            transform: translateX(4px);
        }
        
        .related-article:last-child {
            border-bottom: none;
        }
        
        .related-article-title {
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .related-article-meta {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        /* 文章底部 */
        .article-footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #e2e8f0;
        }
        
        .share-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .share-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .share-weibo {
            background: #ff6b6b;
            color: white;
        }
        
        .share-wechat {
            background: #07c160;
            color: white;
        }
        
        .share-qq {
            background: #1296db;
            color: white;
        }
        
        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .article-tags {
            margin-bottom: 2rem;
        }
        
        .tag {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            text-decoration: none;
        }
        
        .tag:hover {
            background: #e2e8f0;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                gap: 1rem;
            }
            
            .main-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 1rem;
            }
            
            .article-content {
                padding: 1.5rem;
            }
            
            .article-title {
                font-size: 2rem;
            }
            
            .article-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .share-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- 头部导航 -->
    <header class="header">
        <nav class="nav-container">
            <a href="/" class="logo"><?= htmlspecialchars($site_settings['site_name'] ?? '网站技术指南') ?></a>
            <ul class="nav-menu">
                <li><a href="/">首页</a></li>
                <li><a href="/#technologies">技术</a></li>
                <li><a href="/#articles">文章</a></li>
                <li><a href="/admin/">管理后台</a></li>
            </ul>
        </nav>
    </header>
    
    <!-- 面包屑导航 -->
    <nav class="breadcrumb">
        <div class="breadcrumb-container">
            <ul class="breadcrumb-list">
                <li><a href="/">首页</a></li>
                <?php if ($article['category_name']): ?>
                    <li><a href="/category/<?= htmlspecialchars($article['category_slug']) ?>"><?= htmlspecialchars($article['category_name']) ?></a></li>
                <?php endif; ?>
                <li class="current"><?= htmlspecialchars($article['title']) ?></li>
            </ul>
        </div>
    </nav>
    
    <div class="main-container">
        <!-- 主要内容 -->
        <main class="article-content">
            <header class="article-header">
                <?php if ($article['category_name']): ?>
                    <a href="/category/<?= htmlspecialchars($article['category_slug']) ?>" class="article-category">
                        <?= htmlspecialchars($article['category_name']) ?>
                    </a>
                <?php endif; ?>
                
                <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                
                <div class="article-meta">
                    <div class="meta-item">
                        <span>👤</span>
                        <span><?= htmlspecialchars($article['author_name']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span>📅</span>
                        <span><?= date('Y年m月d日', strtotime($article['published_at'])) ?></span>
                    </div>
                    <div class="meta-item">
                        <span>👁️</span>
                        <span><?= number_format($article['views']) ?> 阅读</span>
                    </div>
                </div>
                
                <?php if ($article['featured_image']): ?>
                    <img src="/<?= htmlspecialchars($article['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($article['title']) ?>" 
                         class="article-featured-image">
                <?php endif; ?>
                
                <?php if ($article['excerpt']): ?>
                    <div class="article-excerpt">
                        <?= htmlspecialchars($article['excerpt']) ?>
                    </div>
                <?php endif; ?>
            </header>
            
            <div class="article-body">
                <?= $article['content'] ?>
            </div>
            
            <footer class="article-footer">
                <div class="share-buttons">
                    <a href="#" class="share-btn share-weibo" onclick="shareToWeibo()">
                        <span>📱</span> 分享到微博
                    </a>
                    <a href="#" class="share-btn share-wechat" onclick="shareToWechat()">
                        <span>💬</span> 分享到微信
                    </a>
                    <a href="#" class="share-btn share-qq" onclick="shareToQQ()">
                        <span>🐧</span> 分享到QQ
                    </a>
                </div>
                
                <?php if ($article['category_name']): ?>
                    <div class="article-tags">
                        <a href="/category/<?= htmlspecialchars($article['category_slug']) ?>" class="tag">
                            #<?= htmlspecialchars($article['category_name']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </footer>
        </main>
        
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <!-- 目录 -->
            <div class="sidebar-card">
                <h3 class="sidebar-title">📋 文章目录</h3>
                <ul class="toc" id="toc">
                    <!-- 目录将由JavaScript生成 -->
                </ul>
            </div>
            
            <!-- 相关文章 -->
            <?php if (!empty($related_articles)): ?>
                <div class="sidebar-card">
                    <h3 class="sidebar-title">🔗 相关文章</h3>
                    <div class="related-articles">
                        <?php foreach ($related_articles as $related): ?>
                            <a href="/article/<?= htmlspecialchars($related['slug']) ?>" class="related-article">
                                <div class="related-article-title"><?= htmlspecialchars($related['title']) ?></div>
                                <div class="related-article-meta">
                                    <?= date('Y-m-d', strtotime($related['published_at'])) ?> • 
                                    <?= number_format($related['views']) ?> 阅读
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- 文章信息 -->
            <div class="sidebar-card">
                <h3 class="sidebar-title">📊 文章信息</h3>
                <div class="article-stats">
                    <div class="stat-item">
                        <strong>字数统计:</strong> 
                        <span id="wordCount">计算中...</span>
                    </div>
                    <div class="stat-item">
                        <strong>预计阅读:</strong> 
                        <span id="readTime">计算中...</span>
                    </div>
                    <div class="stat-item">
                        <strong>发布日期:</strong> 
                        <?= date('Y-m-d H:i', strtotime($article['published_at'])) ?>
                    </div>
                    <?php if ($article['updated_at'] !== $article['created_at']): ?>
                        <div class="stat-item">
                            <strong>更新日期:</strong> 
                            <?= date('Y-m-d H:i', strtotime($article['updated_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
    
    <script>
        // 生成目录
        function generateTOC() {
            const toc = document.getElementById('toc');
            const headings = document.querySelectorAll('.article-body h1, .article-body h2, .article-body h3, .article-body h4');
            
            if (headings.length === 0) {
                toc.innerHTML = '<li style="color: #9ca3af; font-style: italic;">暂无目录</li>';
                return;
            }
            
            let tocHTML = '';
            headings.forEach((heading, index) => {
                const id = `heading-${index}`;
                heading.id = id;
                
                const level = parseInt(heading.tagName.substring(1));
                const indent = (level - 1) * 1; // 每级缩进1rem
                
                tocHTML += `
                    <li style="margin-left: ${indent}rem;">
                        <a href="#${id}">${heading.textContent}</a>
                    </li>
                `;
            });
            
            toc.innerHTML = tocHTML;
        }
        
        // 计算文章统计信息
        function calculateStats() {
            const content = document.querySelector('.article-body').textContent;
            const wordCount = content.replace(/\s+/g, '').length; // 中文字符计数
            const readTime = Math.ceil(wordCount / 300); // 假设每分钟阅读300字
            
            document.getElementById('wordCount').textContent = wordCount.toLocaleString() + ' 字';
            document.getElementById('readTime').textContent = readTime + ' 分钟';
        }
        
        // 平滑滚动到锚点
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // 分享功能
        function shareToWeibo() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://service.weibo.com/share/share.php?url=${url}&title=${title}`, '_blank');
        }
        
        function shareToWechat() {
            // 微信分享需要特殊处理，这里简单复制链接
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('链接已复制到剪贴板，可以粘贴到微信分享');
            });
        }
        
        function shareToQQ() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://connect.qq.com/widget/shareqq/index.html?url=${url}&title=${title}`, '_blank');
        }
        
        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            generateTOC();
            calculateStats();
        });
        
        // 代码高亮（简单版本）
        document.querySelectorAll('pre code').forEach((block) => {
            // 添加复制按钮
            const button = document.createElement('button');
            button.textContent = '复制';
            button.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(255,255,255,0.1);
                color: white;
                border: 1px solid rgba(255,255,255,0.3);
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                cursor: pointer;
            `;
            
            const pre = block.parentElement;
            pre.style.position = 'relative';
            pre.appendChild(button);
            
            button.addEventListener('click', () => {
                navigator.clipboard.writeText(block.textContent).then(() => {
                    button.textContent = '已复制';
                    setTimeout(() => {
                        button.textContent = '复制';
                    }, 2000);
                });
            });
        });
        
        // 阅读进度条
        const progressBar = document.createElement('div');
        progressBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            z-index: 9999;
            transition: width 0.3s ease;
        `;
        document.body.appendChild(progressBar);
        
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset;
            const docHeight = document.body.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            progressBar.style.width = scrollPercent + '%';
        });
    </script>
</body>
</html>