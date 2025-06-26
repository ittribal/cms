<?php
// admin/article_delete.php - 删除文章页面
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录状态和权限
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!$auth->hasPermission('article.delete')) {
    die('您没有权限删除文章');
}

$pageTitle = '删除文章';
$currentUser = $auth->getCurrentUser();
$message = '';
$error = '';

// 获取文章ID
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$articleId) {
    header('Location: articles.php?error=' . urlencode('无效的文章ID'));
    exit;
}

// 获取文章数据
$article = $db->fetchOne("
    SELECT a.*, c.name as category_name, u.username as author_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.author_id = u.id
    WHERE a.id = ?
", [$articleId]);

if (!$article) {
    header('Location: articles.php?error=' . urlencode('文章不存在'));
    exit;
}

// 检查删除权限（只能删除自己的文章，除非是管理员）
if ($article['author_id'] != $currentUser['id'] && !$auth->hasPermission('article.manage')) {
    header('Location: articles.php?error=' . urlencode('您只能删除自己的文章'));
    exit;
}

// 处理删除确认
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'confirm_delete') {
        $result = handleArticleDelete($articleId);
        if ($result['success']) {
            header('Location: articles.php?message=' . urlencode($result['message']));
            exit;
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'cancel') {
        header('Location: articles.php');
        exit;
    }
}

// 处理文章删除
function handleArticleDelete($articleId) {
    global $db, $auth, $article;
    
    try {
        // 开始事务
        $db->beginTransaction();
        
        // 删除文章标签关联
        $db->execute("DELETE FROM article_tags WHERE article_id = ?", [$articleId]);
        
        // 删除文章评论（如果有评论系统）
        $db->execute("DELETE FROM comments WHERE article_id = ?", [$articleId]);
        
        // 删除相关文件
        $filesToDelete = [];
        
        // 删除特色图片
        if ($article['featured_image'] && file_exists('../' . $article['featured_image'])) {
            $filesToDelete[] = '../' . $article['featured_image'];
        }
        
        // 查找内容中的图片并删除
        $contentImages = extractImagesFromContent($article['content']);
        foreach ($contentImages as $imagePath) {
            if (file_exists('../' . $imagePath)) {
                $filesToDelete[] = '../' . $imagePath;
            }
        }
        
        // 删除文章记录
        $result = $db->execute("DELETE FROM articles WHERE id = ?", [$articleId]);
        
        if (!$result) {
            throw new Exception('删除文章失败');
        }
        
        // 提交事务
        $db->commit();
        
        // 删除相关文件
        foreach ($filesToDelete as $filePath) {
            try {
                unlink($filePath);
            } catch (Exception $e) {
                error_log('删除文件失败: ' . $filePath . ' - ' . $e->getMessage());
            }
        }
        
        // 记录删除日志
        $auth->logAction('删除文章', '文章ID: ' . $articleId . ', 标题: ' . $article['title']);
        
        return [
            'success' => true, 
            'message' => '文章删除成功！'
        ];
        
    } catch (Exception $e) {
        // 回滚事务
        $db->rollback();
        
        error_log('Article delete error: ' . $e->getMessage());
        return ['success' => false, 'message' => '删除失败：' . $e->getMessage()];
    }
}

// 从内容中提取图片路径
function extractImagesFromContent($content) {
    $images = [];
    
    // 匹配img标签的src属性
    if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        foreach ($matches[1] as $src) {
            // 只处理相对路径的图片
            if (strpos($src, 'uploads/') === 0) {
                $images[] = $src;
            }
        }
    }
    
    return array_unique($images);
}

// 获取文章统计信息
$stats = [
    'comments_count' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE article_id = ?", [$articleId])['count'] ?? 0,
    'tags_count' => $db->fetchOne("SELECT COUNT(*) as count FROM article_tags WHERE article_id = ?", [$articleId])['count'] ?? 0,
    'content_images' => count(extractImagesFromContent($article['content']))
];

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-trash-alt"></i> 删除文章</h1>
                <p>确认删除文章及其相关数据</p>
            </div>
            <div class="header-actions">
                <a href="articles.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
                <a href="article_edit.php?id=<?php echo $article['id']; ?>" class="btn btn-info">
                    <i class="fas fa-edit"></i> 编辑文章
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- 删除确认卡片 -->
        <div class="delete-confirmation">
            <div class="warning-card">
                <div class="warning-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>危险操作</h2>
                </div>
                <div class="warning-body">
                    <p class="warning-text">
                        您即将删除以下文章，此操作<strong>不可撤销</strong>！删除后将同时删除：
                    </p>
                    
                    <ul class="delete-items">
                        <li><i class="fas fa-file-alt"></i> 文章内容和所有元数据</li>
                        <li><i class="fas fa-image"></i> 特色图片和内容中的图片 (<?php echo $stats['content_images'] + ($article['featured_image'] ? 1 : 0); ?> 张)</li>
                        <li><i class="fas fa-tags"></i> 标签关联 (<?php echo $stats['tags_count']; ?> 个)</li>
                        <?php if ($stats['comments_count'] > 0): ?>
                        <li><i class="fas fa-comments"></i> 相关评论 (<?php echo $stats['comments_count']; ?> 条)</li>
                        <?php endif; ?>
                        <li><i class="fas fa-link"></i> SEO数据和访问统计</li>
                    </ul>
                </div>
            </div>

            <!-- 文章信息卡片 -->
            <div class="article-info-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> 文章信息</h3>
                </div>
                <div class="card-body">
                    <div class="article-preview">
                        <?php if ($article['featured_image']): ?>
                        <div class="article-image">
                            <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="特色图片">
                        </div>
                        <?php endif; ?>
                        
                        <div class="article-details">
                            <h4 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h4>
                            
                            <div class="article-meta">
                                <div class="meta-item">
                                    <label>ID:</label>
                                    <span><?php echo $article['id']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>作者:</label>
                                    <span><?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>分类:</label>
                                    <span><?php echo htmlspecialchars($article['category_name'] ?? '未分类'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>状态:</label>
                                    <span class="status-badge status-<?php echo $article['status']; ?>">
                                        <?php 
                                        $statusMap = [
                                            'draft' => '草稿',
                                            'published' => '已发布',
                                            'archived' => '已归档'
                                        ];
                                        echo $statusMap[$article['status']] ?? $article['status'];
                                        ?>
                                    </span>
                                </div>
                                <div class="meta-item">
                                    <label>创建时间:</label>
                                    <span><?php echo date('Y-m-d H:i:s', strtotime($article['created_at'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>浏览量:</label>
                                    <span><?php echo number_format($article['views']); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($article['excerpt']): ?>
                            <div class="article-excerpt">
                                <label>摘要:</label>
                                <p><?php echo htmlspecialchars($article['excerpt']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 删除确认表单 -->
            <form method="POST" class="delete-form">
                <div class="confirmation-input">
                    <label for="confirmText">请输入文章标题以确认删除：</label>
                    <input type="text" id="confirmText" class="form-control" 
                           placeholder="请输入: <?php echo htmlspecialchars($article['title']); ?>"
                           required>
                    <small class="form-text">此步骤是为了防止误删除重要文章</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="action" value="cancel" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 取消
                    </button>
                    <button type="submit" name="action" value="confirm_delete" 
                            id="deleteButton" class="btn btn-danger" disabled>
                        <i class="fas fa-trash-alt"></i> 确认删除
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<style>
/* 删除页面样式 */
.delete-confirmation {
    max-width: 800px;
    margin: 0 auto;
}

.warning-card {
    background: linear-gradient(135deg, #fff5f5, #fed7d7);
    border: 2px solid #feb2b2;
    border-radius: 15px;
    margin-bottom: 2rem;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(254, 178, 178, 0.3);
}

.warning-header {
    background: linear-gradient(135deg, #f56565, #e53e3e);
    color: white;
    padding: 1.5rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.warning-header i {
    font-size: 2rem;
}

.warning-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.warning-body {
    padding: 2rem;
}

.warning-text {
    font-size: 1.1rem;
    color: #742a2a;
    margin-bottom: 1.5rem;
    text-align: center;
    line-height: 1.6;
}

.delete-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.delete-items li {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #e53e3e;
    font-weight: 500;
    color: #742a2a;
}

.delete-items i {
    color: #e53e3e;
    width: 20px;
    text-align: center;
}

/* 文章信息卡片 */
.article-info-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    background: #fafbfc;
}

.card-header h3 {
    margin: 0;
    color: #2c3e50;
    font-weight: 600;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

.article-preview {
    display: flex;
    gap: 1.5rem;
}

.article-image {
    flex-shrink: 0;
    width: 150px;
}

.article-image img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.article-details {
    flex: 1;
}

.article-title {
    color: #2c3e50;
    margin: 0 0 1rem 0;
    font-size: 1.3rem;
    font-weight: 600;
    line-height: 1.4;
}

.article-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meta-item label {
    font-weight: 600;
    color: #495057;
    min-width: 80px;
}

.meta-item span {
    color: #6c757d;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.status-draft {
    background: #fff3cd;
    color: #856404;
}

.status-badge.status-published {
    background: #d4edda;
    color: #155724;
}

.status-badge.status-archived {
    background: #f8d7da;
    color: #721c24;
}

.article-excerpt {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.article-excerpt label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 0.5rem;
}

.article-excerpt p {
    color: #6c757d;
    margin: 0;
    line-height: 1.5;
}

/* 删除表单 */
.delete-form {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 2rem;
    border: 2px solid #feb2b2;
}

.confirmation-input {
    margin-bottom: 2rem;
}

.confirmation-input label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #e53e3e;
    outline: none;
    box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
}

.form-text {
    color: #6c757d;
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn {
    padding: 0.75rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
    min-width: 150px;
    justify-content: center;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border-color: #6c757d;
}

.btn-secondary:hover:not(:disabled) {
    background: #545b62;
    text-decoration: none;
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    color: white;
    border-color: #c53030;
}

.btn-danger:hover:not(:disabled) {
    background: linear-gradient(135deg, #c53030, #9c1e1e);
    color: white;
    text-decoration: none;
}

.btn-info {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    border-color: #138496;
}

.btn-info:hover {
    background: linear-gradient(135deg, #138496, #117a8b);
    color: white;
    text-decoration: none;
}

/* 页面头部 */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}

.header-left h1 {
    color: #e53e3e;
    margin: 0 0 0.5rem 0;
    font-size: 1.8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-left p {
    color: #7f8c8d;
    margin: 0;
    font-size: 0.95rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* 消息提示 */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #e74c3c;
}

.alert i {
    font-size: 1.1rem;
}

/* 响应式设计 */
@media (max-width: 768px) {
    .main-content {
        padding: 1rem;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .header-actions {
        width: 100%;
        flex-direction: column;
    }
    
    .header-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .article-preview {
        flex-direction: column;
    }
    
    .article-image {
        width: 100%;
    }
    
    .article-meta {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<script>
console.log('删除文章页面脚本加载...');

document.addEventListener('DOMContentLoaded', function() {
    const confirmInput = document.getElementById('confirmText');
    const deleteButton = document.getElementById('deleteButton');
    const targetTitle = <?php echo json_encode($article['title'], JSON_UNESCAPED_UNICODE); ?>;
    
    // 监听输入变化
    confirmInput.addEventListener('input', function() {
        const inputValue = this.value.trim();
        const isMatch = inputValue === targetTitle;
        
        deleteButton.disabled = !isMatch;
        
        if (isMatch) {
            confirmInput.style.borderColor = '#27ae60';
            confirmInput.style.backgroundColor = '#f0fff4';
        } else if (inputValue) {
            confirmInput.style.borderColor = '#e53e3e';
            confirmInput.style.backgroundColor = '#fff5f5';
        } else {
            confirmInput.style.borderColor = '#dee2e6';
            confirmInput.style.backgroundColor = 'white';
        }
    });
    
    // 表单提交确认
    document.querySelector('.delete-form').addEventListener('submit', function(e) {
        const action = e.submitter.value;
        
        if (action === 'confirm_delete') {
            const confirmed = confirm('最后确认：您确定要删除这篇文章吗？此操作无法撤销！');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
            
            // 禁用按钮，显示加载状态
            deleteButton.disabled = true;
            deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 删除中...';
        }
    });
    
    // 键盘快捷键
    document.addEventListener('keydown', function(e) {
        // ESC 键取消
        if (e.key === 'Escape') {
            const cancelBtn = document.querySelector('button[value="cancel"]');
            if (cancelBtn) {
                cancelBtn.click();
            }
        }
    });
    
    console.log('删除文章页面脚本初始化完成');
});
</script>

<?php include '../templates/admin_footer.php'; ?>