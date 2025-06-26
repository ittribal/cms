<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';

$db = Database::getInstance();

// 获取网站设置
$settings = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
$siteSettings = [];
foreach ($settings as $setting) {
    $siteSettings[$setting['setting_key']] = $setting['setting_value'];
}

// 获取最新文章
$articles = $db->fetchAll("
    SELECT a.*, c.name as category_name, u.username as author_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.status = 'published' 
    ORDER BY a.created_at DESC 
    LIMIT 20
");

// 设置RSS头部
header('Content-Type: application/rss+xml; charset=UTF-8');

// 生成RSS XML
$rssContent = generateRSS($siteSettings, $articles);
echo $rssContent;

function generateRSS($settings, $articles) {
    $siteTitle = $settings['site_title'] ?? 'CMS网站';
    $siteDescription = $settings['site_description'] ?? '网站描述';
    $siteUrl = SITE_URL . '/public/';
    
    $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $rss .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $rss .= '<channel>' . "\n";
    
    // 频道信息
    $rss .= '<title><![CDATA[' . $siteTitle . ']]></title>' . "\n";
    $rss .= '<description><![CDATA[' . $siteDescription . ']]></description>' . "\n";
    $rss .= '<link>' . $siteUrl . '</link>' . "\n";
    $rss .= '<atom:link href="' . $siteUrl . 'rss.php" rel="self" type="application/rss+xml" />' . "\n";
    $rss .= '<language>zh-CN</language>' . "\n";
    $rss .= '<lastBuildDate>' . date('r') . '</lastBuildDate>' . "\n";
    $rss .= '<generator>CMS System</generator>' . "\n";
    
    // 文章条目
    foreach ($articles as $article) {
        $articleUrl = $siteUrl . 'article.php?slug=' . $article['slug'];
        $pubDate = date('r', strtotime($article['created_at']));
        
        $rss .= '<item>' . "\n";
        $rss .= '<title><![CDATA[' . $article['title'] . ']]></title>' . "\n";
        $rss .= '<description><![CDATA[' . ($article['excerpt'] ?: getExcerpt($article['content'])) . ']]></description>' . "\n";
        $rss .= '<content:encoded><![CDATA[' . $article['content'] . ']]></content:encoded>' . "\n";
        $rss .= '<link>' . $articleUrl . '</link>' . "\n";
        $rss .= '<guid isPermaLink="true">' . $articleUrl . '</guid>' . "\n";
        $rss .= '<pubDate>' . $pubDate . '</pubDate>' . "\n";
        $rss .= '<author>' . $article['author_name'] . '</author>' . "\n";
        
        if ($article['category_name']) {
            $rss .= '<category><![CDATA[' . $article['category_name'] . ']]></category>' . "\n";
        }
        
        $rss .= '</item>' . "\n";
    }
    
    $rss .= '</channel>' . "\n";
    $rss .= '</rss>';
    
    return $rss;
}

function getExcerpt($content, $length = 200) {
    $text = strip_tags($content);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
?>
5. RSS管理页面 (admin/rss.php)
php
<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

$auth = new Auth();

// 检查登录和权限
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$error = '';

// 获取RSS统计信息
$rssStats = [
    'total_articles' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'],
    'recent_articles' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'published' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'],
    'total_views' => $db->fetchOne("SELECT SUM(views) as total FROM articles WHERE status = 'published'")['total'] ?? 0
];

// 获取最新文章
$recentArticles = $db->fetchAll("
    SELECT a.title, a.slug, a.created_at, a.views, u.username as author_name
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.status = 'published' 
    ORDER BY a.created_at DESC 
    LIMIT 10
");

// RSS订阅统计（这里可以集成第三方统计服务）
$subscriptionStats = [
    'feedburner_subscribers' => 0, // 可以通过FeedBurner API获取
    'estimated_readers' => $rssStats['total_views'] / 100 // 简单估算
];
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS管理 - CMS后台</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include '../templates/admin_header.php'; ?>
    
    <div class="admin-container">
        <main class="main-content">
            <div class="page-header">
                <h1>RSS订阅管理</h1>
                <div class="header-actions">
                    <a href="../public/rss.php" target="_blank" class="btn btn-primary">📡 查看RSS源</a>
                </div>
            </div>
            
            <!-- RSS统计 -->
            <div class="rss-stats">
                <h3>RSS统计</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($rssStats['total_articles']); ?></div>
                        <div class="stat-label">可订阅文章</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($rssStats['recent_articles']); ?></div>
                        <div class="stat-label">最近7天新增</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($subscriptionStats['estimated_readers']); ?></div>
                        <div class="stat-label">预估读者数</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($rssStats['total_views']); ?></div>
                        <div class="stat-label">总浏览量</div>
                    </div>
                </div>
            </div>
            
            <!-- RSS链接信息 -->
            <div class="rss-info">
                <h3>RSS订阅信息</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <h4>📡 主RSS源</h4>
                        <div class="rss-url">
                            <input type="text" value="<?php echo SITE_URL; ?>/public/rss.php" readonly class="form-control">
                            <button onclick="copyToClipboard(this.previousElementSibling.value)" class="btn btn-sm btn-outline">复制</button>
                        </div>
                        <p>包含最新20篇已发布文章的完整RSS源</p>
                    </div>
                    
                    <div class="info-item">
                        <h4>🔗 订阅按钮代码</h4>
                        <textarea class="form-control" readonly rows="3">&lt;a href="<?php echo SITE_URL; ?>/public/rss.php"&gt;
    &lt;img src="rss-icon.png" alt="RSS订阅"&gt; 订阅RSS
&lt;/a&gt;</textarea>
                        <p>将此代码添加到您的网站以显示RSS订阅链接</p>
                    </div>
                </div>
            </div>
            
            <!-- 最新RSS内容 -->
            <div class="rss-content">
                <h3>RSS最新内容</h3>
                <div class="articles-list">
                    <?php foreach ($recentArticles as $article): ?>
                        <div class="article-item">
                            <div class="article-info">
                                <h4><?php echo htmlspecialchars($article['title']); ?></h4>
                                <div class="article-meta">
                                    <span class="author">👤 <?php echo htmlspecialchars($article['author_name']); ?></span>
                                    <span class="date">📅 <?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?></span>
                                    <span class="views">👁️ <?php echo number_format($article['views']); ?></span>
                                </div>
                            </div>
                            <div class="article-actions">
                                <a href="../public/article.php?slug=<?php echo $article['slug']; ?>" 
                                   target="_blank" class="btn btn-sm btn-info">查看</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- RSS优化建议 -->
            <div class="rss-recommendations">
                <h3>RSS优化建议</h3>
                <div class="recommendations">
                    <div class="recommendation">
                        <h4>📝 内容质量</h4>
                        <ul>
                            <li>确保文章标题简洁明了</li>
                            <li>为每篇文章添加摘要</li>
                            <li>使用结构化的内容格式</li>
                            <li>定期发布高质量内容</li>
                        </ul>
                    </div>
                    
                    <div class="recommendation">
                        <h4>🚀 推广订阅</h4>
                        <ul>
                            <li>在网站显著位置添加RSS图标</li>
                            <li>在社交媒体推广RSS订阅</li>
                            <li>使用FeedBurner等服务统计订阅数</li>
                            <li>提供邮件订阅选项</li>
                        </ul>
                    </div>
                    
                    <div class="recommendation">
                        <h4>⚡ 技术优化</h4>
                        <ul>
                            <li>确保RSS源格式正确</li>
                            <li>设置合适的缓存时间</li>
                            <li>包含完整的文章内容</li>
                            <li>添加图片和媒体内容</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- RSS验证工具 -->
            <div class="rss-validation">
                <h3>RSS验证工具</h3>
                <div class="validation-tools">
                    <div class="tool-item">
                        <h4>🔍 RSS验证器</h4>
                        <p>验证您的RSS源格式是否正确</p>
                        <a href="https://validator.w3.org/feed/check.cgi?url=<?php echo urlencode(SITE_URL . '/public/rss.php'); ?>" 
                           target="_blank" class="btn btn-primary">验证RSS</a>
                    </div>
                    
                    <div class="tool-item">
                        <h4>📊 FeedBurner</h4>
                        <p>Google的RSS服务，提供详细统计</p>
                        <a href="https://feedburner.google.com/" target="_blank" class="btn btn-info">访问FeedBurner</a>
                    </div>
                    
                    <div class="tool-item">
                        <h4>🔧 RSS调试</h4>
                        <p>测试RSS源的可读性和兼容性</p>
                        <button onclick="testRSSFeed()" class="btn btn-warning">测试RSS源</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('RSS链接已复制到剪贴板');
            }).catch(() => {
                // 降级方案
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('RSS链接已复制到剪贴板');
            });
        }
        
        function testRSSFeed() {
            const rssUrl = '<?php echo SITE_URL; ?>/public/rss.php';
            
            fetch(rssUrl)
                .then(response => response.text())
                .then(data => {
                    if (data.includes('<rss') && data.includes('</rss>')) {
                        alert('✅ RSS源测试通过！格式正确。');
                    } else {
                        alert('❌ RSS源可能有问题，请检查格式。');
                    }
                })
                .catch(error => {
                    alert('❌ RSS源测试失败：' + error.message);
                });
        }
    </script>
    
    <style>
        .rss-stats,
        .rss-info,
        .rss-content,
        .rss-recommendations,
        .rss-validation {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .rss-stats h3,
        .rss-info h3,
        .rss-content h3,
        .rss-recommendations h3,
        .rss-validation h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }
        
        .info-item h4 {
            color: #495057;
            margin-bottom: 1rem;
        }
        
        .rss-url {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .rss-url input {
            flex: 1;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #6c757d;
            color: #6c757d;
        }
        
        .btn-outline:hover {
            background: #6c757d;
            color: white;
        }
        
        .articles-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .article-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .article-item:last-child {
            border-bottom: none;
        }
        
        .article-info h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .article-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .recommendations {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .recommendation {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }
        
        .recommendation h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .recommendation ul {
            color: #495057;
            margin-left: 1rem;
        }
        
        .recommendation li {
            margin-bottom: 0.5rem;
        }
        
        .validation-tools {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .tool-item {
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            text-align: center;
        }
        
        .tool-item h4 {
            color: #495057;
            margin-bottom: 1rem;
        }
        
        .tool-item p {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .header-actions {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</body>
</html>