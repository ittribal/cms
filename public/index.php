<?php
// public/index.php - 网站首页

// --- 重要：ABSPATH 和其他常量在 config.php 中定义。
// --- 它必须是包含的第一个文件。
require_once __DIR__ . '/../includes/config.php'; 

// 引入其他核心类和函数，它们现在可以安全地使用 ABSPATH 了
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php';

$db = Database::getInstance(); // 获取数据库单例

// 获取最新文章
$latest_articles = $db->fetchAll(
    "SELECT a.id, a.title, a.slug, a.excerpt, a.featured_image, a.published_at, a.views,
            u.username as author_name, c.name as category_name, c.slug as category_slug
     FROM articles a
     JOIN users u ON a.author_id = u.id  /* 统一用户表，author_id 指向 users.id */
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE a.status = 'published'
     ORDER BY a.published_at DESC
     LIMIT 6"
);

// 获取热门文章
$popular_articles = $db->fetchAll(
    "SELECT a.id, a.title, a.slug, a.excerpt, a.featured_image, a.views, a.published_at /* 确保包含 excerpt 和 published_at */
     FROM articles a
     JOIN users u ON a.author_id = u.id  /* 统一用户表，author_id 指向 users.id */
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE a.status = 'published'
     ORDER BY a.views DESC
     LIMIT 3"
);

// 设置页面元信息，将在 public_header.php 中使用
$page_title = '首页';
$page_description = '欢迎来到我们的CMS网站，探索最新的技术文章和教程。';
$page_keywords = '技术, 编程, 教程, CMS, PHP';
$page_specific_css = 'home'; // 告诉 public_header.php 引入 home.css

include ABSPATH . 'public/templates/public_header.php'; // 引入公共头部模板
?>

<section class="hero-section">
    <div class="container">
        <h1>探索前沿技术，掌握编程艺术</h1>
        <p>我们提供高质量的技术文章、教程和实战经验分享。</p>
        <div class="hero-buttons">
            <a href="#latest-articles" class="btn btn-primary">最新文章</a>
            <a href="#popular-articles" class="btn btn-secondary">热门推荐</a>
        </div>
    </div>
</section>

<section id="latest-articles" class="articles-section">
    <div class="container">
        <h2><i class="fas fa-newspaper"></i> 最新文章</h2>
        <div class="article-grid">
            <?php if (!empty($latest_articles)): ?>
                <?php foreach ($latest_articles as $article): ?>
                    <?php 
                    // 确保 article_card.php 能够访问到 $article 变量
                    include ABSPATH . 'public/templates/partials/article_card.php'; 
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-content">暂无最新文章。</p>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/public/articles.php" class="btn btn-outline">查看所有文章 <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<section id="popular-articles" class="articles-section bg-light">
    <div class="container">
        <h2><i class="fas fa-fire"></i> 热门文章</h2>
        <div class="article-grid popular-grid">
            <?php if (!empty($popular_articles)): ?>
                <?php foreach ($popular_articles as $article): ?>
                    <?php 
                    // 确保 article_card.php 能够访问到 $article 变量
                    include ABSPATH . 'public/templates/partials/article_card.php'; 
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-content">暂无热门文章。</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="call-to-action">
    <div class="container text-center">
        <h2>加入我们，共同成长！</h2>
        <p>订阅我们的博客，获取最新的技术动态和学习资源。</p>
        <a href="<?= SITE_URL ?>/public/subscribe.php" class="btn btn-lg">立即订阅</a>
    </div>
</section>

<?php include ABSPATH . 'public/templates/public_footer.php'; // 引入公共底部模板 ?>