<?php
// public/templates/public_header.php - 前台公共头部模板

// 确保 $page_title, $page_description 等变量在包含此文件前已设置
$page_title = $page_title ?? SITE_TITLE;
$page_description = $page_description ?? SITE_DESCRIPTION;
$page_keywords = $page_keywords ?? '技术, 编程, 教程, CMS, PHP'; // 默认关键词
$current_url = SITE_URL . $_SERVER['REQUEST_URI'];
$og_image = $og_image ?? SITE_URL . '/public/assets/images/default-og-image.jpg'; // 默认OG图

// 注意：这里不再需要 require_once includes/config.php; 因为 public/index.php 已经包含了
// 但需要确保 SITE_URL, SITE_TITLE, SITE_DESCRIPTION 等常量在 public/index.php 中定义前已经被 config.php 加载。
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($page_title) ?> | <?= esc_html(SITE_TITLE) ?></title>
    <meta name="description" content="<?= esc_attr($page_description) ?>">
    <meta name="keywords" content="<?= esc_attr($page_keywords) ?>">

    <meta property="og:title" content="<?= esc_attr($page_title) ?>">
    <meta property="og:description" content="<?= esc_attr($page_description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= esc_url($current_url) ?>">
    <meta property="og:image" content="<?= esc_url($og_image) ?>">
    <meta property="og:site_name" content="<?= esc_attr(SITE_TITLE) ?>">

    <link rel="icon" href="<?= SITE_URL ?>/public/assets/favicon.ico" type="image/x-icon">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="<?= SITE_URL ?>/public/assets/css/style.css">
    <?php if (isset($page_specific_css)): ?>
        <link rel="stylesheet" href="<?= SITE_URL ?>/public/assets/css/<?= esc_attr($page_specific_css) ?>.css">
    <?php endif; ?>

    </head>
<body data-theme="light"> <header class="site-header">
        <div class="container main-nav">
            <a href="<?= SITE_URL ?>/public/index.php" class="site-logo"><?= esc_html(SITE_TITLE) ?></a>
            <nav class="main-menu">
                <ul>
                    <li><a href="<?= SITE_URL ?>/public/index.php">首页</a></li>
                    <li><a href="<?= SITE_URL ?>/public/articles.php">文章</a></li>
                    <li><a href="<?= SITE_URL ?>/public/categories.php">分类</a></li>
                    <li><a href="<?= SITE_URL ?>/public/tags.php">标签</a></li>
                    <li><a href="<?= SITE_URL ?>/public/about.php">关于我们</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/dashboard.php" target="_blank">后台管理</a></li>
                </ul>
            </nav>
            <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="切换主题">
                <i class="fas fa-moon"></i>
            </button>
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <nav class="mobile-menu" id="mobileMenu">
            <ul>
                <li><a href="<?= SITE_URL ?>/public/index.php">首页</a></li>
                <li><a href="<?= SITE_URL ?>/public/articles.php">文章</a></li>
                <li><a href="<?= SITE_URL ?>/public/categories.php">分类</a></li>
                <li><a href="<?= SITE_URL ?>/public/tags.php">标签</a></li>
                <li><a href="<?= SITE_URL ?>/public/about.php">关于我们</a></li>
                <li><a href="<?= SITE_URL ?>/admin/dashboard.php" target="_blank">后台管理</a></li>
            </ul>
        </nav>
    </header>
    <main class="site-main">