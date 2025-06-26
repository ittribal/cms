<?php
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

$pageTitle = '评论管理';
$currentUser = $auth->getCurrentUser();
$message = '';
$error = '';

// 获取筛选参数
$status = $_GET['status'] ?? '';
$article = $_GET['article'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($status) {
    $where[] = "c.status = ?";
    $params[] = $status;
}

if ($article) {
    $where[] = "c.article_id = ?";
    $params[] = $article;
}

if ($search) {
    $where[] = "(c.content LIKE ? OR c.author_name LIKE ? OR c.author_email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 获取评论列表
$sql = "SELECT c.*, a.title as article_title 
        FROM comments c 
        LEFT JOIN articles a ON c.article_id = a.id 
        WHERE {$whereClause}
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?";

$comments = $db->fetchAll($sql, array_merge($params, [$limit, $offset]));

// 获取总数
$countSql = "SELECT COUNT(*) as total FROM comments c WHERE {$whereClause}";
$totalCount = $db->fetchOne($countSql, $params)['total'];
$totalPages = ceil($totalCount / $limit);

// 获取统计数据
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM comments")['count'],
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'")['count'],
    'approved' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'approved'")['count'],
    'spam' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'spam'")['count']
];

include '../templates/admin_header.php';
?>

<div class="comments-page">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-comments"></i> 评论管理</h1>
                <p>管理网站评论，审核、回复和删除评论</p>
            </div>
            <div class="header-actions">
                <button onclick="refreshComments()" class="btn btn-info">
                    <i class="fas fa-sync-alt"></i> 刷新
                </button>
                <a href="../index.php" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt"></i> 查看网站
                </a>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #3498db;">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">评论总数</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f39c12;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
                    <div class="stat-label">待审核</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #27ae60;">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['approved']); ?></div>
                    <div class="stat-label">已通过</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e74c3c;">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['spam']); ?></div>
                    <div class="stat-label">垃圾评论</div>
                </div>
            </div>
        </div>

        <!-- 筛选器 -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <select name="status" class="form-control">
                        <option value="">所有状态</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>已通过</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>已拒绝</option>
                        <option value="spam" <?php echo $status === 'spam' ? 'selected' : ''; ?>>垃圾评论</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="article" class="form-control">
                        <option value="">所有文章</option>
                        <?php
                        $articles = $db->fetchAll("SELECT DISTINCT a.id, a.title FROM articles a 
                                                  INNER JOIN comments c ON a.id = c.article_id 
                                                  ORDER BY a.title");
                        foreach ($articles as $articleItem) {
                            $selected = $article == $articleItem['id'] ? 'selected' : '';
                            echo "<option value='{$articleItem['id']}' {$selected}>" . htmlspecialchars($articleItem['title']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="search-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="搜索评论内容、作者..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="comments.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 清除
                    </a>
                </div>
            </form>
        </div>

        <!-- 评论列表 -->
        <div class="content-card">
            <div class="card-header">
                <h3>评论列表</h3>
            </div>
            
            <div class="comments-container">
                <?php if (empty($comments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <p>暂无评论数据</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div class="comment-meta">
                                    <div class="comment-author">
                                        <div class="author-avatar">
                                            <?php echo strtoupper(substr($comment['author_name'], 0, 2)); ?>
                                        </div>
                                        <div class="author-info">
                                            <div class="author-name"><?php echo htmlspecialchars($comment['author_name']); ?></div>
                                            <div class="author-email"><?php echo htmlspecialchars($comment['author_email']); ?></div>
                                        </div>
                                    </div>
                                    <div class="comment-date"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></div>
                                    <?php if ($comment['article_title']): ?>
                                        <a href="../article.php?id=<?php echo $comment['article_id']; ?>" 
                                           class="comment-article" target="_blank">
                                            <?php echo htmlspecialchars($comment['article_title']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="comment-actions">
                                    <div class="quick-actions">
                                        <?php if ($comment['status'] !== 'approved'): ?>
                                            <button class="quick-action-btn btn-approve" 
                                                    title="通过" onclick="updateStatus(<?php echo $comment['id']; ?>, 'approved')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($comment['status'] !== 'rejected'): ?>
                                            <button class="quick-action-btn btn-reject" 
                                                    title="拒绝" onclick="updateStatus(<?php echo $comment['id']; ?>, 'rejected')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="quick-action-btn btn-spam" 
                                                title="标记垃圾" onclick="updateStatus(<?php echo $comment['id']; ?>, 'spam')">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        
                                        <button class="quick-action-btn btn-reply" 
                                                title="回复" onclick="showReplyModal(<?php echo $comment['id']; ?>)">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                        
                                        <button class="quick-action-btn btn-delete" 
                                                title="删除" onclick="deleteComment(<?php echo $comment['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                            
                            <div class="comment-status">
                                <span class="status-badge status-<?php echo $comment['status']; ?>">
                                    <?php
                                    $statusLabels = [
                                        'pending' => '待审核',
                                        'approved' => '已通过',
                                        'rejected' => '已拒绝',
                                        'spam' => '垃圾评论'
                                    ];
                                    echo $statusLabels[$comment['status']] ?? $comment['status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- 回复模态框 -->
    <div id="replyModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-reply"></i> 回复评论</h3>
                <button type="button" class="close" onclick="closeModal('replyModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="originalComment" class="original-comment"></div>
                
                <form id="replyForm" method="POST">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="parent_id" id="parentId">
                    
                    <div class="form-group">
                        <label for="replyContent">回复内容</label>
                        <textarea id="replyContent" name="content" class="form-control" 
                                  rows="5" required placeholder="输入您的回复..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> 发送回复
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('replyModal')">
                            取消
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* 评论管理样式 */
.comments-page .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.comments-page .stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.comments-page .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.comments-page .stat-content {
    flex: 1;
}

.comments-page .stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
    line-height: 1;
}

.comments-page .stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

.comments-page .filter-section {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.comments-page .filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) 2fr;
    gap: 1rem;
    align-items: end;
}

.comments-page .search-group {
    display: flex;
    gap: 0.5rem;
}

.comments-page .search-group input {
    flex: 1;
}

.comments-page .comments-container {
    padding: 0;
}

.comments-page .comment-item {
    border-bottom: 1px solid #eee;
    padding: 1.5rem;
    transition: background-color 0.2s ease;
}

.comments-page .comment-item:hover {
    background-color: #f8f9fa;
}

.comments-page .comment-item:last-child {
    border-bottom: none;
}

.comments-page .comment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.comments-page .comment-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.comments-page .comment-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.comments-page .author-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #3498db;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.8rem;
}

.comments-page .author-info {
    display: flex;
    flex-direction: column;
}

.comments-page .author-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.comments-page .author-email {
    color: #6c757d;
    font-size: 0.8rem;
}

.comments-page .comment-date {
    color: #6c757d;
    font-size: 0.85rem;
}

.comments-page .comment-article {
    color: #3498db;
    text-decoration: none;
    font-size: 0.85rem;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.comments-page .comment-article:hover {
    text-decoration: underline;
}

.comments-page .comment-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.comments-page .quick-actions {
    display: flex;
    gap: 0.25rem;
}

.comments-page .quick-action-btn {
    width: 28px;
    height: 28px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    background: white;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.8rem;
}

.comments-page .quick-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.comments-page .quick-action-btn.btn-approve:hover {
    background: #27ae60;
    color: white;
    border-color: #27ae60;
}

.comments-page .quick-action-btn.btn-reject:hover {
    background: #e74c3c;
    color: white;
    border-color: #e74c3c;
}

.comments-page .quick-action-btn.btn-spam:hover {
    background: #6c757d;
    color: white;
    border-color: #6c757d;
}

.comments-page .quick-action-btn.btn-reply:hover {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.comments-page .quick-action-btn.btn-delete:hover {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

.comments-page .comment-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
    margin-left: 2rem;
    line-height: 1.6;
    color: #495057;
    position: relative;
}

.comments-page .comment-content:before {
    content: '';
    position: absolute;
    left: -8px;
    top: 15px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #f8f9fa transparent transparent;
}

.comments-page .comment-status {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    margin-left: 2rem;
}

.comments-page .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.comments-page .status-pending {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.comments-page .status-approved {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.comments-page .status-rejected {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.comments-page .status-spam {
    background: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

.comments-page .empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.comments-page .empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.comments-page .empty-state p {
    font-size: 1.1rem;
}

.comments-page .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.comments-page .modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.comments-page .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 10px 10px 0 0;
}

.comments-page .modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.comments-page .close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
    background: none;
    border: none;
    padding: 0;
    line-height: 1;
}

.comments-page .close:hover {
    color: #000;
}

.comments-page .modal-body {
    padding: 1.5rem;
}

/* 响应式设计 */
@media (max-width: 768px) {
    .comments-page .comment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .comments-page .comment-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .comments-page .comment-content {
        margin-left: 0;
    }
    
    .comments-page .comment-content:before {
        display: none;
    }
    
    .comments-page .comment-status {
        margin-left: 0;
    }
    
    .comments-page .filter-form {
        grid-template-columns: 1fr;
    }
    
    .comments-page .search-group {
        flex-direction: column;
    }
}
</style>

<script>
// 更新评论状态
function updateStatus(commentId, status) {
    const statusTexts = {
        'approved': '通过',
        'rejected': '拒绝',
        'spam': '标记为垃圾'
    };
    
    if (confirm(`确定要${statusTexts[status]}这条评论吗？`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.innerHTML = `
            <input name="action" value="${status}">
            <input name="id" value="${commentId}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 删除评论
function deleteComment(commentId) {
    if (confirm('确定要删除这条评论吗？删除后无法恢复。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.innerHTML = `
            <input name="action" value="delete">
            <input name="id" value="${commentId}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 显示回复模态框
function showReplyModal(commentId) {
    document.getElementById('parentId').value = commentId;
    document.getElementById('replyModal').style.display = 'block';
}

// 关闭模态框
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// 刷新评论
function refreshComments() {
    window.location.reload();
}

// 点击外部关闭模态框
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
};
</script>

<?php include '../templates/admin_footer.php'; ?>