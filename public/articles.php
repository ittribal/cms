<?php
// public/article.php - 前台文章详情页

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/Database.php';

$db = Database::getInstance();

$slug = sanitize_input($_GET['slug'] ?? '');

if (empty($slug)) {
    // header('HTTP/1.0 404 Not Found'); // 实际部署中应使用
    include '404.php';
    exit;
}

// 获取文章详情
$article = $db->fetchOne(
    "SELECT a.*, u.username as author_name, c.name as category_name, c.slug as category_slug
     FROM articles a
     JOIN users u ON a.author_id = u.id
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE a.slug = ? AND a.status = 'published'",
    [$slug]
);

if (!$article) {
    // header('HTTP/1.0 404 Not Found'); // 实际部署中应使用
    include '404.php';
    exit;
}

// 增加浏览量 (后台逻辑，前端不需要调用API，访问页面即增加)
// 异步增加浏览量，避免阻塞页面加载
$db->execute("UPDATE articles SET views = views + 1 WHERE id = ?", [$article['id']]);


// 获取相关文章 (同分类，排除当前文章，按发布时间倒序)
$related_articles = $db->fetchAll(
    "SELECT a.id, a.title, a.slug, a.featured_image, a.published_at, a.views,
            c.name as category_name, c.slug as category_slug
     FROM articles a
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE a.id != ? AND a.category_id = ? AND a.status = 'published'
     ORDER BY a.published_at DESC
     LIMIT 4",
    [$article['id'], $article['category_id']]
);

// 获取文章评论 (假设 comments 表存在)
$comments = $db->fetchAll(
    "SELECT * FROM comments WHERE article_id = ? AND status = 'approved' ORDER BY created_at ASC",
    [$article['id']]
);


// 设置页面元信息
$page_title = esc_html($article['meta_title'] ?: $article['title']);
$page_description = esc_html($article['meta_description'] ?: $article['excerpt']);
$page_keywords = esc_attr($article['meta_keywords'] ?? ($article['category_name'] ? $article['category_name'] . ',技术文档,编程教程' : '技术文档,编程教程'));
$og_image = $article['featured_image'] ? SITE_URL . '/' . esc_attr($article['featured_image']) : null;
$page_specific_css = 'article'; // 引入 article.css
$page_specific_js = 'article'; // 引入 article.js

include 'templates/public_header.php';
?>

<div class="container article-container">
    <article class="article-detail">
        <header class="article-header">
            <nav class="breadcrumb">
                <a href="<?= SITE_URL ?>/public/index.php">首页</a> &gt;
                <?php if ($article['category_name']): ?>
                    <a href="<?= SITE_URL ?>/public/category.php?slug=<?= esc_attr($article['category_slug']) ?>"><?= esc_html($article['category_name']) ?></a> &gt;
                <?php endif; ?>
                <span><?= esc_html($article['title']) ?></span>
            </nav>
            <h1 class="article-title"><?= esc_html($article['title']) ?></h1>
            <div class="article-meta">
                <span><i class="fas fa-user"></i> <?= esc_html($article['author_name']) ?></span>
                <span><i class="fas fa-calendar-alt"></i> <?= date('Y年m月d日', strtotime($article['published_at'])) ?></span>
                <span><i class="fas fa-eye"></i> <?= number_format($article['views']) ?> 阅读</span>
            </div>
            <?php if ($article['featured_image']): ?>
                <img src="<?= SITE_URL ?>/<?= esc_attr($article['featured_image']) ?>" alt="<?= esc_attr($article['title']) ?>" class="featured-image">
            <?php endif; ?>
            <?php if ($article['excerpt']): ?>
                <p class="article-excerpt"><?= esc_html($article['excerpt']) ?></p>
            <?php endif; ?>
        </header>

        <div class="article-content" id="articleContent">
            <?= $article['content'] ?>
        </div>

        <footer class="article-footer">
            <div class="share-buttons">
                <h4>分享到:</h4>
                <a href="#" class="share-btn weibo" onclick="shareTo('weibo', '<?= esc_url($current_url) ?>', '<?= esc_attr($page_title) ?>')"><i class="fab fa-weibo"></i> 微博</a>
                <a href="#" class="share-btn wechat" onclick="shareTo('wechat', '<?= esc_url($current_url) ?>', '<?= esc_attr($page_title) ?>')"><i class="fab fa-weixin"></i> 微信</a>
                <a href="#" class="share-btn qq" onclick="shareTo('qq', '<?= esc_url($current_url) ?>', '<?= esc_attr($page_title) ?>')"><i class="fab fa-qq"></i> QQ</a>
            </div>
        </footer>
    </article>

    <aside class="sidebar">
        <div class="widget table-of-contents">
            <h3><i class="fas fa-list-alt"></i> 文章目录</h3>
            <ul id="tocList">
                </ul>
        </div>

        <?php if (!empty($related_articles)): ?>
        <div class="widget related-articles-widget">
            <h3><i class="fas fa-link"></i> 相关文章</h3>
            <div class="article-list-small">
                <?php foreach ($related_articles as $rel_article): ?>
                <div class="article-item-small">
                    <a href="<?= SITE_URL ?>/public/article.php?slug=<?= esc_attr($rel_article['slug']) ?>" class="article-title-small">
                        <?= esc_html($rel_article['title']) ?>
                    </a>
                    <div class="article-meta-small">
                        <span><?= date('Y-m-d', strtotime($rel_article['published_at'])) ?></span>
                        <span><?= number_format($rel_article['views']) ?> 阅读</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </aside>
</div>

<?php include 'templates/partials/comment_section.php'; ?>

<?php include 'templates/public_footer.php'; ?>