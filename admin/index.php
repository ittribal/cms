<?php
// admin/index.php - 后台管理首页/仪表盘
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录状态
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = '仪表盘';
$currentUser = $auth->getCurrentUser();

// 安全的数据库查询函数
function safeQuery($db, $sql, $params = []) {
    try {
        $result = $db->fetchOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return 0;
    }
}

function safeQueryAll($db, $sql, $params = []) {
    try {
        $result = $db->fetchAll($sql, $params);
        return $result ? $result : [];
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return [];
    }
}

// 获取统计数据 - 使用安全查询
$stats = [
    // 文章统计
    'articles' => [
        'total' => safeQuery($db, "SELECT COUNT(*) as count FROM articles WHERE status != 'trash'"),
        'published' => safeQuery($db, "SELECT COUNT(*) as count FROM articles WHERE status = 'published'"),
        'draft' => safeQuery($db, "SELECT COUNT(*) as count FROM articles WHERE status = 'draft'"),
        'today' => safeQuery($db, "SELECT COUNT(*) as count FROM articles WHERE DATE(created_at) = CURDATE()")
    ],
    
    // 用户统计
    'users' => [
        'total' => safeQuery($db, "SELECT COUNT(*) as count FROM users"),
        'active' => safeQuery($db, "SELECT COUNT(*) as count FROM users WHERE status = 'active'"),
        'new_today' => safeQuery($db, "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")
    ],
    
    // 评论统计
    'comments' => [
        'total' => safeQuery($db, "SELECT COUNT(*) as count FROM comments"),
        'pending' => safeQuery($db, "SELECT COUNT(*) as count FROM comments WHERE status = 'pending'"),
        'approved' => safeQuery($db, "SELECT COUNT(*) as count FROM comments WHERE status = 'approved'"),
        'today' => safeQuery($db, "SELECT COUNT(*) as count FROM comments WHERE DATE(created_at) = CURDATE()")
    ],
    
    // 分类统计
    'categories' => [
        'total' => safeQuery($db, "SELECT COUNT(*) as count FROM categories"),
        'active' => safeQuery($db, "SELECT COUNT(*) as count FROM categories WHERE status = 'active'")
    ]
];

// 获取最近文章
$recentArticles = safeQueryAll($db, "
    SELECT a.id, a.title, a.status, a.created_at, a.views, 
           COALESCE(c.name, '未分类') as category_name, 
           COALESCE(u.real_name, u.username) as author_name
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE a.status != 'trash'
    ORDER BY a.created_at DESC 
    LIMIT 10
");

// 获取待审核评论
$pendingComments = safeQueryAll($db, "
    SELECT c.id, c.author_name, c.content, c.created_at,
           COALESCE(a.title, '已删除文章') as article_title, 
           COALESCE(a.slug, '') as article_slug
    FROM comments c 
    LEFT JOIN articles a ON c.article_id = a.id 
    WHERE c.status = 'pending' 
    ORDER BY c.created_at DESC 
    LIMIT 8
");

// 获取热门文章
$popularArticles = safeQueryAll($db, "
    SELECT a.id, a.title, a.views, a.created_at,
           COALESCE(c.name, '未分类') as category_name
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.status = 'published' 
    ORDER BY a.views DESC 
    LIMIT 8
");

// 获取最近用户
$recentUsers = safeQueryAll($db, "
    SELECT id, username, real_name, email, role, status, created_at, last_login_at
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 8
");

// 获取系统信息
$systemInfo = [
    'php_version' => PHP_VERSION,
    'server_time' => date('Y-m-d H:i:s'),
    'disk_usage' => [
        'free' => disk_free_space('.') ?: 0,
        'total' => disk_total_space('.') ?: 0
    ]
];

// 尝试获取MySQL版本
try {
    $mysqlVersion = $db->fetchOne("SELECT VERSION() as version");
    $systemInfo['mysql_version'] = $mysqlVersion ? $mysqlVersion['version'] : '未知';
} catch (Exception $e) {
    $systemInfo['mysql_version'] = '获取失败';
}

// 格式化文件大小
function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';
    
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// 获取最近7天的数据用于图表
$chartData = [
    'dates' => [],
    'articles' => [],
    'comments' => [],
    'users' => []
];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $chartData['dates'][] = date('m-d', strtotime("-{$i} days"));
    
    $chartData['articles'][] = safeQuery($db, "SELECT COUNT(*) as count FROM articles WHERE DATE(created_at) = ?", [$date]);
    $chartData['comments'][] = safeQuery($db, "SELECT COUNT(*) as count FROM comments WHERE DATE(created_at) = ?", [$date]);
    $chartData['users'][] = safeQuery($db, "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = ?", [$date]);
}

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
        <!-- 欢迎区域 -->
        <div class="welcome-section">
            <div class="welcome-card">
                <div class="welcome-content">
                    <h2>👋 欢迎回来，<?php echo htmlspecialchars($currentUser['real_name'] ?? $currentUser['username']); ?>！</h2>
                    <p>今天是 <?php echo date('Y年m月d日 H:i'); ?>，祝您工作愉快！</p>
                </div>
                <div class="welcome-stats">
                    <div class="quick-stat">
                        <span class="stat-number"><?php echo $stats['articles']['today']; ?></span>
                        <span class="stat-label">今日新增文章</span>
                    </div>
                    <div class="quick-stat">
                        <span class="stat-number"><?php echo $stats['comments']['today']; ?></span>
                        <span class="stat-label">今日新增评论</span>
                    </div>
                    <div class="quick-stat">
                        <span class="stat-number"><?php echo $stats['users']['new_today']; ?></span>
                        <span class="stat-label">今日新增用户</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card articles">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['articles']['total']); ?></div>
                    <div class="stat-label">文章总数</div>
                    <div class="stat-detail">
                        <span class="stat-item">📝 已发布: <?php echo $stats['articles']['published']; ?></span>
                        <span class="stat-item">📋 草稿: <?php echo $stats['articles']['draft']; ?></span>
                    </div>
                </div>
                <div class="stat-action">
                    <a href="articles.php" class="btn btn-sm">管理文章</a>
                </div>
            </div>

            <div class="stat-card users">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['users']['total']); ?></div>
                    <div class="stat-label">用户总数</div>
                    <div class="stat-detail">
                        <span class="stat-item">✅ 活跃: <?php echo $stats['users']['active']; ?></span>
                        <span class="stat-item">🆕 今日新增: <?php echo $stats['users']['new_today']; ?></span>
                    </div>
                </div>
                <div class="stat-action">
                    <a href="users.php" class="btn btn-sm">管理用户</a>
                </div>
            </div>

            <div class="stat-card comments">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['comments']['total']); ?></div>
                    <div class="stat-label">评论总数</div>
                    <div class="stat-detail">
                        <span class="stat-item">⏳ 待审核: <?php echo $stats['comments']['pending']; ?></span>
                        <span class="stat-item">✅ 已通过: <?php echo $stats['comments']['approved']; ?></span>
                    </div>
                </div>
                <div class="stat-action">
                    <a href="comments.php" class="btn btn-sm">管理评论</a>
                </div>
            </div>

            <div class="stat-card categories">
                <div class="stat-icon">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['categories']['total']); ?></div>
                    <div class="stat-label">分类总数</div>
                    <div class="stat-detail">
                        <span class="stat-item">🟢 启用: <?php echo $stats['categories']['active']; ?></span>
                        <span class="stat-item">📂 分类管理</span>
                    </div>
                </div>
                <div class="stat-action">
                    <a href="categories.php" class="btn btn-sm">管理分类</a>
                </div>
            </div>
        </div>

        <!-- 图表区域 -->
        <div class="chart-section">
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">📈 最近7天数据趋势</h3>
                    <div class="chart-legend">
                        <span class="legend-item articles">📝 文章</span>
                        <span class="legend-item comments">💬 评论</span>
                        <span class="legend-item users">👥 用户</span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart" width="800" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- 内容管理区域 -->
        <div class="dashboard-grid">
            <!-- 最近文章 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">📝 最近文章</h3>
                    <a href="articles.php" class="card-action">查看全部</a>
                </div>
                <div class="list-container">
                    <?php if (empty($recentArticles)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>暂无文章</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentArticles as $article): ?>
                            <div class="list-item">
                                <div class="item-content">
                                    <h4 class="item-title">
                                        <a href="articles.php?action=edit&id=<?php echo $article['id']; ?>">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h4>
                                    <div class="item-meta">
                                        <span class="status-badge status-<?php echo $article['status']; ?>">
                                            <?php echo ucfirst($article['status']); ?>
                                        </span>
                                        <span>👁️ <?php echo number_format($article['views']); ?></span>
                                        <span>👤 <?php echo htmlspecialchars($article['author_name']); ?></span>
                                        <span>📁 <?php echo htmlspecialchars($article['category_name']); ?></span>
                                    </div>
                                </div>
                                <div class="item-time">
                                    <?php echo date('m-d H:i', strtotime($article['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 待审核评论 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">⏳ 待审核评论</h3>
                    <a href="comments.php?status=pending" class="card-action">查看全部</a>
                </div>
                <div class="list-container">
                    <?php if (empty($pendingComments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>暂无待审核评论</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pendingComments as $comment): ?>
                            <div class="list-item comment-item">
                                <div class="comment-avatar">
                                    <?php echo strtoupper(substr($comment['author_name'], 0, 1)); ?>
                                </div>
                                <div class="item-content">
                                    <h4 class="item-title">
                                        <?php echo htmlspecialchars($comment['author_name']); ?>
                                    </h4>
                                    <p class="comment-content">
                                        <?php echo htmlspecialchars(mb_substr($comment['content'], 0, 100)); ?>
                                        <?php if (mb_strlen($comment['content']) > 100): ?>...<?php endif; ?>
                                    </p>
                                    <div class="item-meta">
                                        <span>📄 <?php echo htmlspecialchars($comment['article_title']); ?></span>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <a href="comments.php?action=approve&id=<?php echo $comment['id']; ?>" 
                                       class="btn-action btn-success" title="通过">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="comments.php?action=view&id=<?php echo $comment['id']; ?>" 
                                       class="btn-action btn-info" title="查看">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 热门文章 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">🔥 热门文章</h3>
                    <a href="articles.php" class="card-action">查看全部</a>
                </div>
                <div class="list-container">
                    <?php if (empty($popularArticles)): ?>
                        <div class="empty-state">
                            <i class="fas fa-fire"></i>
                            <p>暂无热门文章</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($popularArticles as $i => $article): ?>
                            <div class="list-item popular-item">
                                <div class="rank-number rank-<?php echo $i + 1; ?>">
                                    <?php echo $i + 1; ?>
                                </div>
                                <div class="item-content">
                                    <h4 class="item-title">
                                        <a href="articles.php?action=edit&id=<?php echo $article['id']; ?>">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h4>
                                    <div class="item-meta">
                                        <span>👁️ <?php echo number_format($article['views']); ?> 次浏览</span>
                                        <span>📁 <?php echo htmlspecialchars($article['category_name']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 最近用户 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">👥 最近用户</h3>
                    <a href="users.php" class="card-action">查看全部</a>
                </div>
                <div class="list-container">
                    <?php if (empty($recentUsers)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>暂无用户</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentUsers as $user): ?>
                            <div class="list-item user-item">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <div class="item-content">
                                    <h4 class="item-title">
                                        <a href="users.php?action=edit&id=<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['real_name'] ?: $user['username']); ?>
                                        </a>
                                    </h4>
                                    <div class="item-meta">
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                        <?php if ($user['last_login_at']): ?>
                                            <span>🕐 <?php echo date('m-d H:i', strtotime($user['last_login_at'])); ?></span>
                                        <?php else: ?>
                                            <span>🆕 新用户</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 快捷操作和系统信息 -->
        <div class="dashboard-footer">
            <!-- 快捷操作 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">🚀 快捷操作</h3>
                </div>
                <div class="quick-actions">
                    <?php if ($auth->hasPermission('article.create')): ?>
                        <a href="articles.php?action=add" class="quick-action">
                            <i class="fas fa-plus"></i>
                            <span>写文章</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasPermission('user.create')): ?>
                        <a href="users.php?action=add" class="quick-action">
                            <i class="fas fa-user-plus"></i>
                            <span>添加用户</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasPermission('category.create')): ?>
                        <a href="categories.php?action=add" class="quick-action">
                            <i class="fas fa-folder-plus"></i>
                            <span>添加分类</span>
                        </a>
                    <?php endif; ?>
                    
                    <a href="../public/index.php" target="_blank" class="quick-action">
                        <i class="fas fa-external-link-alt"></i>
                        <span>查看网站</span>
                    </a>
                    
                    <?php if ($auth->hasPermission('setting.edit')): ?>
                        <a href="settings.php" class="quick-action">
                            <i class="fas fa-cogs"></i>
                            <span>系统设置</span>
                        </a>
                    <?php endif; ?>
                    
                    <a href="media.php" class="quick-action">
                        <i class="fas fa-images"></i>
                        <span>媒体库</span>
                    </a>
                </div>
            </div>

            <!-- 系统信息 -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">💻 系统信息</h3>
                </div>
                <div class="system-info">
                    <div class="info-item">
                        <span class="info-label">PHP版本:</span>
                        <span class="info-value"><?php echo $systemInfo['php_version']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">MySQL版本:</span>
                        <span class="info-value"><?php echo $systemInfo['mysql_version']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">服务器时间:</span>
                        <span class="info-value"><?php echo $systemInfo['server_time']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">磁盘使用:</span>
                        <span class="info-value">
                            <?php echo formatBytes($systemInfo['disk_usage']['total'] - $systemInfo['disk_usage']['free']); ?> / 
                            <?php echo formatBytes($systemInfo['disk_usage']['total']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">可用空间:</span>
                        <span class="info-value"><?php echo formatBytes($systemInfo['disk_usage']['free']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .content-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .welcome-section {
        margin-bottom: 2rem;
    }
    
    .welcome-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .welcome-content h2 {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .welcome-content p {
        opacity: 0.9;
        font-size: 1rem;
    }
    
    .welcome-stats {
        display: flex;
        gap: 2rem;
    }
    
    .quick-stat {
        text-align: center;
    }
    
    .quick-stat .stat-number {
        display: block;
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }
    
    .quick-stat .stat-label {
        font-size: 0.85rem;
        opacity: 0.9;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--card-color);
    }
    
    .stat-card.articles {
        --card-color: #3498db;
    }
    
    .stat-card.users {
        --card-color: #2ecc71;
    }
    
    .stat-card.comments {
        --card-color: #f39c12;
    }
    
    .stat-card.categories {
        --card-color: #9b59b6;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--card-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #2c3e50;
        line-height: 1;
    }
    
    .stat-label {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 0.25rem 0;
    }
    
    .stat-detail {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .stat-item {
        font-size: 0.8rem;
        color: #95a5a6;
    }
    
    .stat-action {
        flex-shrink: 0;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        background: #2980b9;
        transform: translateY(-1px);
    }
    
    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
    
    .chart-section {
        margin-bottom: 2rem;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }
    
    .card-title {
        color: #2c3e50;
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0;
    }
    
    .card-action {
        color: #3498db;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .card-action:hover {
        text-decoration: underline;
    }
    
    .chart-legend {
        display: flex;
        gap: 1rem;
    }
    
    .legend-item {
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }
    
    .legend-item.articles {
        background: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }
    
    .legend-item.comments {
        background: rgba(243, 156, 18, 0.1);
        color: #f39c12;
    }
    
    .legend-item.users {
        background: rgba(46, 204, 113, 0.1);
        color: #2ecc71;
    }
    
    .chart-container {
        height: 300px;
        position: relative;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .list-container {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .list-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s ease;
    }
    
    .list-item:hover {
        background: #f8f9fa;
    }
    
    .list-item:last-child {
        border-bottom: none;
    }
    
    .item-content {
        flex: 1;
    }
    
    .item-title {
        margin: 0 0 0.5rem 0;
        font-size: 0.95rem;
        font-weight: 500;
    }
    
    .item-title a {
        color: #2c3e50;
        text-decoration: none;
    }
    
    .item-title a:hover {
        color: #3498db;
    }
    
    .item-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        font-size: 0.8rem;
        color: #7f8c8d;
    }
    
    .status-badge {
        padding: 0.2rem 0.5rem;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 500;
        text-transform: capitalize;
    }
    
    .status-published {
        background: #d4edda;
        color: #155724;
    }
    
    .status-draft {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-approved {
        background: #d4edda;
        color: #155724;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .role-badge {
        padding: 0.2rem 0.5rem;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 500;
        text-transform: capitalize;
    }
    
    .role-super_admin {
        background: #8e44ad;
        color: white;
    }
    
    .role-admin {
        background: #e74c3c;
        color: white;
    }
    
    .role-editor {
        background: #f39c12;
        color: white;
    }
    
    .role-author {
        background: #27ae60;
        color: white;
    }
    
    .role-subscriber {
        background: #95a5a6;
        color: white;
    }
    
    .item-time {
        color: #95a5a6;
        font-size: 0.8rem;
        white-space: nowrap;
    }
    
    .item-actions {
        display: flex;
        gap: 0.25rem;
    }
    
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        background: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
    }
    
    .btn-action:hover {
        transform: translateY(-1px);
    }
    
    .btn-action.btn-success:hover {
        background: #27ae60;
        color: white;
        border-color: #27ae60;
    }
    
    .btn-action.btn-info:hover {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }
    
    .comment-item {
        align-items: flex-start;
    }
    
    .comment-avatar,
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    
    .comment-content {
        color: #495057;
        line-height: 1.4;
        margin: 0.25rem 0;
        font-size: 0.9rem;
    }
    
    .popular-item {
        align-items: center;
    }
    
    .rank-number {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
        color: white;
        flex-shrink: 0;
    }
    
    .rank-1 {
        background: #f39c12;
    }
    
    .rank-2 {
        background: #95a5a6;
    }
    
    .rank-3 {
        background: #cd7f32;
    }
    
    .rank-number:not(.rank-1):not(.rank-2):not(.rank-3) {
        background: #bdc3c7;
    }
    
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #95a5a6;
    }
    
    .empty-state i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        opacity: 0.5;
    }
    
    .dashboard-footer {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }
    
    .quick-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1.5rem 1rem;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        text-decoration: none;
        color: #495057;
        transition: all 0.3s ease;
    }
    
    .quick-action:hover {
        background: #3498db;
        color: white;
        border-color: #3498db;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }
    
    .quick-action i {
        font-size: 1.5rem;
    }
    
    .quick-action span {
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .system-info {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #7f8c8d;
        font-weight: 500;
    }
    
    .info-value {
        color: #2c3e50;
        font-weight: 600;
    }
    
    @media (max-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .dashboard-footer {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .welcome-card {
            flex-direction: column;
            gap: 1.5rem;
            text-align: center;
        }
        
        .welcome-stats {
            flex-direction: row;
            justify-content: space-around;
            width: 100%;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .item-meta {
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .list-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .item-actions {
            align-self: flex-end;
        }
    }
    
    @media (max-width: 480px) {
        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .welcome-stats {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>

<!-- Chart.js 图表库 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
    // 初始化图表
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trendChart');
        if (ctx) {
            const chartData = <?php echo json_encode($chartData); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.dates,
                    datasets: [
                        {
                            label: '文章',
                            data: chartData.articles,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: '评论',
                            data: chartData.comments,
                            borderColor: '#f39c12',
                            backgroundColor: 'rgba(243, 156, 18, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: '用户',
                            data: chartData.users,
                            borderColor: '#2ecc71',
                            backgroundColor: 'rgba(46, 204, 113, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 4,
                            hoverRadius: 6
                        }
                    }
                }
            });
        }
    });
</script>
<?php include '../templates/admin_footer.php'; ?>
</body>
</html>