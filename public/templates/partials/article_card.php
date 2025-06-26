<?php
// public/templates/partials/article_card.php - 文章卡片组件

// 确保 $article 变量已在父级作用域定义
if (!isset($article)) {
    return; // 如果没有文章数据，则不渲染
}

// 格式化日期和浏览量 (确保 functions.php 已被包含)
$published_date = date('Y年m月d日', strtotime($article['published_at'] ?? '')); // <--- 修复点：使用 ?? 运算符提供默认值
$views_formatted = number_format($article['views'] ?? 0); // <--- 修复点：使用 ?? 运算符提供默认值
$author_name = esc_html($article['author_name'] ?? '佚名');
$category_name = esc_html($article['category_name'] ?? '未分类');
$category_slug = esc_attr($article['category_slug'] ?? 'uncategorized');
$article_slug = esc_attr($article['slug']);
$article_title = esc_html($article['title']);
$article_excerpt = esc_html($article['excerpt'] ?? '暂无摘要'); // <--- 修复点：使用 ?? 运算符提供默认值
// 默认文章图片路径
$default_article_image = SITE_URL . '/public/assets/images/default-article.jpg';

// 确保 featured_image 的路径正确构建，处理其可能为空或不规范的情况
$featured_image_src = '';
if (!empty($article['featured_image'])) {
    // 假设 $article['featured_image'] 已经是一个类似 'uploads/path/image.jpg' 的相对路径
    $featured_image_src = SITE_URL . '/' . esc_attr($article['featured_image']);
} else {
    $featured_image_src = $default_article_image;
}
?>

<div class="article-card card">
    <a href="<?= SITE_URL ?>/public/article.php?slug=<?= $article_slug ?>" class="card-image-link">
        <img src="<?= $featured_image_src ?>" alt="<?= $article_title ?>" class="card-image" loading="lazy" data-src="<?= $featured_image_src ?>">
    </a>
    <div class="card-content">
        <div class="card-meta top-meta">
            <a href="<?= SITE_URL ?>/public/category.php?slug=<?= $category_slug ?>" class="category-badge"><?= $category_name ?></a>
        </div>
        <h3 class="card-title">
            <a href="<?= SITE_URL ?>/public/article.php?slug=<?= $article_slug ?>"><?= $article_title ?></a>
        </h3>
        <p class="card-excerpt"><?= $article_excerpt ?></p>
        <div class="card-meta bottom-meta">
            <span><i class="fas fa-user"></i> <?= $author_name ?></span>
            <span><i class="fas fa-calendar-alt"></i> <?= $published_date ?></span>
            <span><i class="fas fa-eye"></i> <?= $views_formatted ?></span>
        </div>
    </div>
</div>