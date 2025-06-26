
<?php
// ==================== frontend/index.php - 前台首页 ====================
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();

// 检查维护模式
$maintenance = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode'");
if ($maintenance && $maintenance['setting_value'] == '1') {
    $message = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_message'");
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>网站维护中</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                text-align: center; 
                padding: 50px; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            .maintenance-box {
                background: rgba(255,255,255,0.1);
                backdrop-filter: blur(10px);
                padding: 40px;
                border-radius: 16px;
                max-width: 500px;
                margin: 0 auto;
            }
        </style>
    </head>
    <body>
        <div class="maintenance-box">
            <h1>🔧 网站维护中</h1>
            <p><?= htmlspecialchars($message['setting_value'] ?? '网站正在维护中，请稍后访问。') ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 获取网站设置
$site_settings = [];
$settings_query = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
foreach ($settings_query as $setting) {
    $site_settings[$setting['setting_key']] = $setting['setting_value'];
}

// 获取分类
$categories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name");

// 获取最新文章
$latest_articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name 
     FROM articles a 
     LEFT JOIN categories c ON a.category_id = c.id 
     WHERE a.status = 'published' 
     ORDER BY a.published_at DESC 
     LIMIT 6"
);

// 获取热门文章
$popular_articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name 
     FROM articles a 
     LEFT JOIN categories c ON a.category_id = c.id 
     WHERE a.status = 'published' 
     ORDER BY a.views DESC 
     LIMIT 4"
);

$page_title = $site_settings['site_name'] ?? '网站技术指南';
$page_description = $site_settings['site_description'] ?? '专业的网站技术学习平台';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
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
        
        /* Hero区域 */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: rgba(255,255,255,0.2);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .btn-primary:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
        }
        
        /* 技术分类展示 */
        .tech-categories {
            padding: 4rem 0;
            background: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #1e293b;
        }
        
        .section-title p {
            font-size: 1.1rem;
            color: #64748b;
        }
        
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .tech-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .tech-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .tech-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .tech-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .tech-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1e293b;
        }
        
        .tech-card p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        
        .tech-features {
            list-style: none;
        }
        
        .tech-features li {
            padding: 0.25rem 0;
            color: #475569;
            font-size: 0.9rem;
        }
        
        .tech-features li::before {
            content: '✨';
            margin-right: 8px;
        }
        
        /* 最新文章 */
        .latest-articles {
            padding: 4rem 0;
            background: #f8fafc;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .article-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .article-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .article-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .article-content {
            padding: 1.5rem;
        }
        
        .article-category {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .article-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        
        .article-title a {
            color: inherit;
            text-decoration: none;
        }
        
        .article-title a:hover {
            color: #667eea;
        }
        
        .article-excerpt {
            color: #64748b;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        /* 页脚 */
        .footer {
            background: #1e293b;
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            margin-bottom: 1rem;
            color: #f1f5f9;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section ul li a {
            color: #cbd5e1;
            text-decoration: none;
        }
        
        .footer-section ul li a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid #334155;
            padding-top: 1rem;
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
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
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .tech-grid,
            .articles-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- 头部导航 -->
    <header class="header">
        <nav class="nav-container">
            <div class="logo"><?= htmlspecialchars($page_title) ?></div>
            <ul class="nav-menu">
                <li><a href="#home">首页</a></li>
                <li><a href="#technologies">技术</a></li>
                <li><a href="#articles">文章</a></li>
                <li><a href="/admin/">管理后台</a></li>
            </ul>
        </nav>
    </header>
    
    <!-- Hero区域 -->
    <section class="hero" id="home">
        <div class="hero-container">
            <h1>现代网站技术指南</h1>
            <p><?= htmlspecialchars($page_description) ?></p>
            <div class="hero-buttons">
                <a href="#technologies" class="btn btn-primary">
                    <span>🚀</span> 开始学习
                </a>
                <a href="#articles" class="btn btn-outline">
                    <span>📚</span> 浏览文章
                </a>
            </div>
        </div>
    </section>
    
    <!-- 技术分类 -->
    <section class="tech-categories" id="technologies">
        <div class="container">
            <div class="section-title">
                <h2>技术栈完全指南</h2>
                <p>从前端到后端，从设计到数据库，掌握现代Web开发的核心技术</p>
            </div>
            
            <div class="tech-grid">
                <div class="tech-card">
                    <div class="tech-icon">🎨</div>
                    <h3>HTML5 & CSS3</h3>
                    <p>现代网页结构与样式设计的基础技术</p>
                    <ul class="tech-features">
                        <li>语义化标签设计</li>
                        <li>响应式布局</li>
                        <li>CSS3动画效果</li>
                        <li>Flexbox & Grid</li>
                    </ul>
                </div>
                
                <div class="tech-card">
                    <div class="tech-icon">⚡</div>
                    <h3>JavaScript</h3>
                    <p>动态交互与现代前端开发核心</p>
                    <ul class="tech-features">
                        <li>ES6+ 现代语法</li>
                        <li>DOM操作技巧</li>
                        <li>异步编程</li>
                        <li>前端框架应用</li>
                    </ul>
                </div>
                
                <div class="tech-card">
                    <div class="tech-icon">🐘</div>
                    <h3>PHP 开发</h3>
                    <p>强大的服务端脚本语言</p>
                    <ul class="tech-features">
                        <li>面向对象编程</li>
                        <li>MVC架构模式</li>
                        <li>安全编程实践</li>
                        <li>RESTful API</li>
                    </ul>
                </div>
                
                <div class="tech-card">
                    <div class="tech-icon">🗄️</div>
                    <h3>MySQL 数据库</h3>
                    <p>高性能关系型数据库管理</p>
                    <ul class="tech-features">
                        <li>查询优化技术</li>
                        <li>索引设计策略</li>
                        <li>数据库安全</li>
                        <li>性能监控</li>
                    </ul>
                </div>
                
                <div class="tech-card">
                    <div class="tech-icon">🎯</div>
                    <h3>实战项目</h3>
                    <p>完整的项目开发流程</p>
                    <ul class="tech-features">
                        <li>项目架构设计</li>
                        <li>代码版本管理</li>
                        <li>测试与部署</li>
                        <li>性能优化</li>
                    </ul>
                </div>
                
                <div class="tech-card">
                    <div class="tech-icon">🔧</div>
                    <h3>开发工具</h3>
                    <p>提升开发效率的工具链</p>
                    <ul class="tech-features">
                        <li>代码编辑器配置</li>
                        <li>调试工具使用</li>
                        <li>自动化构建</li>
                        <li>部署与运维</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 最新文章 -->
    <?php if (!empty($latest_articles)): ?>
    <section class="latest-articles" id="articles">
        <div class="container">
            <div class="section-title">
                <h2>最新技术文章</h2>
                <p>深入浅出的技术教程，助你快速掌握最新技术</p>
            </div>
            
            <div class="articles-grid">
                <?php foreach ($latest_articles as $article): ?>
                    <article class="article-card">
                        <div class="article-image">
                            <?php
                            $icons = ['🎨', '⚡', '🐘', '🗄️', '🎯', '🔧'];
                            echo $icons[array_rand($icons)];
                            ?>
                        </div>
                        <div class="article-content">
                            <?php if ($article['category_name']): ?>
                                <span class="article-category"><?= htmlspecialchars($article['category_name']) ?></span>
                            <?php endif; ?>
                            <h3 class="article-title">
                                <a href="article.php?slug=<?= htmlspecialchars($article['slug']) ?>">
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h3>
                            <p class="article-excerpt">
                                <?= htmlspecialchars(mb_substr(strip_tags($article['excerpt'] ?: $article['content']), 0, 120)) ?>...
                            </p>
                            <div class="article-meta">
                                <span><?= date('Y-m-d', strtotime($article['published_at'])) ?></span>
                                <span><?= number_format($article['views']) ?> 阅读</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>快速链接</h3>
                    <ul>
                        <li><a href="#home">首页</a></li>
                        <li><a href="#technologies">技术栈</a></li>
                        <li><a href="#articles">文章</a></li>
                        <li><a href="/admin/">管理后台</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>技术分类</h3>
                    <ul>
                        <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                            <li><a href="category.php?slug=<?= htmlspecialchars($category['slug']) ?>"><?= htmlspecialchars($category['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>关于我们</h3>
                    <ul>
                        <li><a href="#">关于网站</a></li>
                        <li><a href="#">联系我们</a></li>
                        <li><a href="#">隐私政策</a></li>
                        <li><a href="#">使用条款</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>关注我们</h3>
                    <p>获取最新的技术资讯和教程更新</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 <?= htmlspecialchars($page_title) ?>. 保留所有权利。</p>
            </div>
        </div>
    </footer>
</body>
</html>