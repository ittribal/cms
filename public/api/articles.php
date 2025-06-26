<?php
// public/api/articles.php - 获取文章列表API

require_once __DIR__ . '/../includes/config.php'; // 这是 public/x.php 到 includes/config.php 的绝对可靠路径
require_once ABSPATH . 'includes/functions.php';
require_once ABSPATH . 'includes/Database.php';

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

try {
    // 获取筛选参数
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = ADMIN_ITEMS_PER_PAGE; // 可以定义一个 FRONTEND_ITEMS_PER_PAGE
    $offset = ($page - 1) * $limit;
    
    $category_slug = sanitize_input($_GET['category'] ?? '');
    $tag_slug = sanitize_input($_GET['tag'] ?? '');
    $search_query = sanitize_input($_GET['search'] ?? '');

    $where_conditions = ["a.status = 'published'"];
    $params = [];

    if (!empty($category_slug)) {
        $category = $db->fetchOne("SELECT id FROM categories WHERE slug = ?", [$category_slug]);
        if ($category) {
            $where_conditions[] = "a.category_id = ?";
            $params[] = $category['id'];
        } else {
            json_response(['success' => false, 'message' => '分类不存在'], 404);
        }
    }

    if (!empty($tag_slug)) {
        $tag = $db->fetchOne("SELECT id FROM tags WHERE slug = ?", [$tag_slug]);
        if ($tag) {
            $where_conditions[] = "EXISTS (SELECT 1 FROM article_tags WHERE article_id = a.id AND tag_id = ?)";
            $params[] = $tag['id'];
        } else {
            json_response(['success' => false, 'message' => '标签不存在'], 404);
        }
    }

    if (!empty($search_query)) {
        $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
        $params[] = "%{$search_query}%";
        $params[] = "%{$search_query}%";
        $params[] = "%{$search_query}%";
    }

    $where_clause = implode(' AND ', $where_conditions);

    // 获取文章总数
    $total_articles = $db->fetchOne("SELECT COUNT(*) as count FROM articles a WHERE {$where_clause}", $params)['count'];
    
    // 获取文章列表
    $articles = $db->fetchAll(
        "SELECT a.id, a.title, a.slug, a.excerpt, a.featured_image, a.published_at, a.views,
                u.username as author_name, c.name as category_name, c.slug as category_slug
         FROM articles a
         JOIN users u ON a.author_id = u.id
         LEFT JOIN categories c ON a.category_id = c.id
         WHERE {$where_clause}
         ORDER BY a.published_at DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$limit, $offset])
    );

    // 格式化数据，例如图片URL，时间
    foreach ($articles as &$article) {
        $article['featured_image_url'] = $article['featured_image'] ? SITE_URL . '/' . esc_attr($article['featured_image']) : null;
        $article['published_date_formatted'] = date('Y年m月d日', strtotime($article['published_at']));
        $article['views_formatted'] = number_format($article['views']);
        // 确保 JSON 输出中的 HTML 内容是安全的
        $article['title'] = esc_html($article['title']);
        $article['excerpt'] = esc_html($article['excerpt']);
    }

    json_response([
        'success' => true,
        'articles' => $articles,
        'total_items' => $total_articles,
        'total_pages' => ceil($total_articles / $limit),
        'current_page' => $page,
        'items_per_page' => $limit
    ]);

} catch (Exception $e) {
    json_response(['success' => false, 'message' => '无法获取文章: ' . $e->getMessage()], 500);
}
?>