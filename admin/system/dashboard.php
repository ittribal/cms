<?php // <-- 确保这是文件的第一行第一列，前面没有任何字符
// admin/system/dashboard.php - 仪表盘

// 核心引导：确保 config.php 首先被引入，并使用 $_SERVER['DOCUMENT_ROOT']
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php'; 

// 引入其他核心类和函数
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php';
require_once ABSPATH . 'includes/Auth.php';

$db = Database::getInstance();
$auth = Auth::getInstance();
$auth->requireLogin(); // 强制要求登录

// 获取统计数据
$stats = [
    'users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'], /* 统一用户表 */
    'articles' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status != 'archived'")['count'], // 不统计归档文章
    'categories' => $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'],
    'media' => $db->fetchOne("SELECT COUNT(*) as count FROM media_files")['count']
];

// 获取最新文章
$recent_articles = $db->fetchAll(
    "SELECT a.id, a.title, a.slug, a.status, a.created_at,
            u.username as author_name, c.name as category_name
     FROM articles a 
     JOIN users u ON a.author_id = u.id /* 统一用户表 */
     LEFT JOIN categories c ON a.category_id = c.id 
     WHERE a.status = 'published' /* 只显示已发布的文章 */
     ORDER BY a.created_at DESC 
     LIMIT 5"
);

// 获取最新日志
$recent_logs = $db->fetchAll(
    "SELECT l.action, l.created_at, u.username 
     FROM admin_logs l 
     LEFT JOIN users u ON l.user_id = u.id /* 统一用户表 */
     ORDER BY l.created_at DESC 
     LIMIT 10"
);

$currentUser = $auth->getCurrentUser();

// 设置页面标题，将在 admin_header.php 中使用
$pageTitle = '控制台';

// 获取并显示闪存消息
$flash_message = get_flash_message();

include ABSPATH . 'templates/admin_header.php'; // 引入后台头部模板
?>

<main class="content">
    <div class="page-header">
        <h1 class="page-title">控制台</h1>
        <p class="page-subtitle">欢迎回来，<?= esc_html($currentUser['username']) ?>！这里是您的管理中心。</p>
    </div>
    
    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_message['type'] ?>">
            <i class="fas fa-<?= $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash_message['message']) ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">管理用户</div>
                <div class="stat-icon" style="background: #e3f2fd; color: #1976d2;">👥</div>
            </div>
            <div class="stat-value"><?= number_format($stats['users']) ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">文章数量</div>
                <div class="stat-icon" style="background: #f3e5f5; color: #7b1fa2;">📝</div>
            </div>
            <div class="stat-value"><?= number_format($stats['articles']) ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">内容分类</div>
                <div class="stat-icon" style="background: #e8f5e8; color: #388e3c;">📂</div>
            </div>
            <div class="stat-value"><?= number_format($stats['categories']) ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">媒体文件</div>
                <div class="stat-icon" style="background: #fff3e0; color: #f57c00;">🖼️</div>
            </div>
            <div class="stat-value"><?= number_format($stats['media']) ?></div>
        </div>
    </div>
    
    <div class="content-grid">
        <div class="content-section">
            <h2 class="section-title">最新文章</h2>
            <?php if (empty($recent_articles)): ?>
                <p style="color: #999; text-align: center; padding: 2rem;">暂无文章</p>
            <?php else: ?>
                <?php foreach ($recent_articles as $article): ?>
                    <div class="article-item">
                        <div class="article-title"><?= esc_html($article['title']) ?></div>
                        <div class="article-meta">
                            <span class="status-badge status-<?= esc_attr($article['status']) ?>">
                                <?php
                                switch($article['status']) {
                                    case 'published': echo '已发布'; break;
                                    case 'draft': echo '草稿'; break;
                                    case 'archived': echo '已归档'; break;
                                    case 'pending': echo '待审核'; break;
                                    case 'private': echo '私密'; break;
                                    default: echo esc_html($article['status']); break;
                                }
                                ?>
                            </span>
                            由 <?= esc_html($article['author_name']) ?> 发布 • 
                            <?= time_ago($article['created_at']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="content-section">
            <h2 class="section-title">最新日志</h2>
            <?php if (empty($recent_logs)): ?>
                <p style="color: #999; text-align: center; padding: 2rem;">暂无日志</p>
            <?php else: ?>
                <?php foreach ($recent_logs as $log): ?>
                    <div class="log-item">
                        <div style="font-weight: 500; color: #333; margin-bottom: 4px;">
                            <?= esc_html($log['action']) ?>
                        </div>
                        <div class="log-meta">
                            <?= esc_html($log['username'] ?? '系统') ?> • 
                            <?= time_ago($log['created_at']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include ABSPATH . 'templates/admin_footer.php'; // 引入后台底部模板 ?>