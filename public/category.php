<?php
// public/category.php - 分类文章列表页

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/Database.php';

$db = Database::getInstance();

$slug = sanitize_input($_GET['slug'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = POSTS_PER_PAGE;

$category = $db->fetchOne(
    "SELECT id, name, slug, description FROM categories WHERE slug = ? AND status = 'active'",
    [$slug]
);

if (!$category) {
    include '404.php';
    exit;
}

// 获取分类下的文章
$total_articles = $db->fetchOne(
    "SELECT COUNT(*) as count FROM articles WHERE category_id = ? AND status = 'published'",
    [$category['id']]
)['count'];

$pagination = paginate($total_articles, $page, $items_per_page);

$articles = $db->fetchAll(
    "SELECT a.id, a.title, a.slug, a.excerpt, a.featured_image, a.published_at, a.views,
            u.username as author_name, c.name as category_name, c.slug as category_slug
     FROM articles a
     JOIN users u ON a.author_id = u.id
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE a.category_id = ? AND a.status = 'published'
     ORDER BY a.published_at DESC
     LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}",
    [$category['id']]
);

// 设置页面元信息
$page_title = esc_html($category['name']);
$page_description = esc_html($category['description'] ?: "探索关于“{$category['name']}”分类的最新文章。");
$page_specific_css = 'list'; // 引入 list.css

include 'templates/public_header.php';
?>

<div class="container page-list">
    <header class="page-list-header">
        <h1><i class="fas fa-folder-open"></i> 分类: <?= esc_html($category['name']) ?></h1>
        <?php if ($category['description']): ?>
            <p class="category-description"><?= esc_html($category['description']) ?></p>
        <?php endif; ?>
    </header>

    <div class="article-list-grid">
        <?php if (!empty($articles)): ?>
            <?php foreach ($articles as $article): ?>
                <?php include 'templates/partials/article_card.php'; // 复用文章卡片组件 ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-content">该分类下暂无文章。</p>
        <?php endif; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <nav class="pagination-nav">
            <?php if ($pagination['has_prev']): ?>
                <a href="<?= SITE_URL ?>/public/category.php?slug=<?= esc_attr($slug) ?>&page=<?= $pagination['prev_page'] ?>" class="page-nav-btn">&laquo; 上一页</a>
            <?php endif; ?>
            <span class="page-info">第 <?= $pagination['current_page'] ?> / <?= $pagination['total_pages'] ?> 页</span>
            <?php if ($pagination['has_next']): ?>
                <a href="<?= SITE_URL ?>/public/category.php?slug=<?= esc_attr($slug) ?>&page=<?= $pagination['next_page'] ?>" class="page-nav-btn">下一页 &raquo;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>

<?php include 'templates/public_footer.php'; ?>