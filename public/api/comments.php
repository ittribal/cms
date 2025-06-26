<?php
// public/api/comments.php - 评论提交API

require_once __DIR__ . '/../includes/config.php'; // 这是 public/x.php 到 includes/config.php 的绝对可靠路径
require_once ABSPATH . 'includes/functions.php';
require_once ABSPATH . 'includes/Database.php';

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => '只允许POST请求'], 405);
}

try {
    // CSRF 保护 (如果前台评论表单也有 CSRF token)
    // $csrf_token = $_POST['csrf_token'] ?? '';
    // if (!verify_csrf_token($csrf_token)) {
    //     json_response(['success' => false, 'message' => 'CSRF验证失败'], 403);
    // }

    $article_id = intval($_POST['article_id'] ?? 0);
    $author_name = sanitize_input($_POST['author_name'] ?? '');
    $author_email = sanitize_input($_POST['author_email'] ?? '');
    $comment_content = sanitize_input($_POST['comment_content'] ?? ''); // 注意：如果允许HTML评论，这里需要HTML净化

    // 验证输入
    if (empty($article_id) || empty($author_name) || empty($author_email) || empty($comment_content)) {
        json_response(['success' => false, 'message' => '所有字段都是必填的'], 400);
    }
    if (!is_valid_email($author_email)) {
        json_response(['success' => false, 'message' => '邮箱格式不正确'], 400);
    }

    // 检查文章是否存在且可评论 (假设文章有 allow_comments 字段)
    $article = $db->fetchOne("SELECT id, title FROM articles WHERE id = ? AND status = 'published'", [$article_id]);
    if (!$article) {
        json_response(['success' => false, 'message' => '文章不存在或不可评论'], 404);
    }

    // 插入评论 (状态默认为 pending，等待后台审核)
    $comment_id = $db->insert('comments', [
        'article_id' => $article_id,
        'author_name' => $author_name,
        'author_email' => $author_email,
        'content' => $comment_content,
        'status' => 'pending', // 新评论通常需要审核
        'parent_id' => intval($_POST['parent_id'] ?? 0), // 支持回复
        'ip_address' => get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    if ($comment_id) {
        // 假设你需要通知管理员或作者有新评论
        // EmailService::getInstance()->sendSystemNotification('comment_notification', SITE_ADMIN_EMAIL, ['article_title' => $article['title']]);
        json_response(['success' => true, 'message' => '评论提交成功，等待审核。']);
    } else {
        json_response(['success' => false, 'message' => '评论提交失败，请重试。'], 500);
    }

} catch (Exception $e) {
    json_response(['success' => false, 'message' => '服务器错误: ' . $e->getMessage()], 500);
}