<?php
// ==================== admin/content/article_delete.php - 删除文章 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);

$article_id = intval($_GET['id'] ?? 0);
if (!$article_id) {
    set_flash_message('参数错误', 'error');
    header('Location: articles.php');
    exit;
}

// 获取文章信息
$article = $db->fetchOne("SELECT * FROM articles WHERE id = ?", [$article_id]);
if (!$article) {
    set_flash_message('文章不存在', 'error');
    header('Location: articles.php');
    exit;
}

// 权限检查
if (!$auth->hasPermission('content.delete') && $article['author_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('权限不足');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF验证失败', 'error');
    } else {
        try {
            // 删除特色图片文件
            if (!empty($article['featured_image']) && file_exists($article['featured_image'])) {
                unlink($article['featured_image']);
            }
            
            // 删除文章
            $db->delete('articles', 'id = ?', [$article_id]);
            
            $auth->logAction($_SESSION['user_id'], 'article_delete', 'articles', $article_id, [
                'title' => $article['title']
            ]);
            
            set_flash_message('文章删除成功', 'success');
        } catch (Exception $e) {
            set_flash_message('删除失败: ' . $e->getMessage(), 'error');
        }
    }
    
    header('Location: articles.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>删除文章 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">删除文章</h1>
                <div class="page-actions">
                    <a href="articles.php" class="btn btn-outline">
                        <span class="icon">←</span> 返回列表
                    </a>
                </div>
            </div>
            
            <div class="content-card">
                <div class="delete-warning">
                    <div class="warning-icon">⚠️</div>
                    <h3>确认删除文章</h3>
                    <p>您即将删除文章 <strong>"<?= htmlspecialchars($article['title']) ?>"</strong>，此操作不可恢复。</p>
                    
                    <div class="article-preview">
                        <div class="article-info">
                            <div class="info-row">
                                <label>标题:</label>
                                <span><?= htmlspecialchars($article['title']) ?></span>
                            </div>
                            <div class="info-row">
                                <label>状态:</label>
                                <span class="status-badge status-<?= $article['status'] ?>">
                                    <?php
                                    switch($article['status']) {
                                        case 'published': echo '已发布'; break;
                                        case 'draft': echo '草稿'; break;
                                        case 'archived': echo '已归档'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <label>创建时间:</label>
                                <span><?= date('Y-m-d H:i:s', strtotime($article['created_at'])) ?></span>
                            </div>
                            <div class="info-row">
                                <label>浏览次数:</label>
                                <span><?= number_format($article['views']) ?></span>
                            </div>
                            <?php if ($article['featured_image']): ?>
                                <div class="info-row">
                                    <label>特色图片:</label>
                                    <span>是（将同时删除）</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($article['excerpt']): ?>
                            <div class="article-excerpt">
                                <h4>摘要:</h4>
                                <p><?= htmlspecialchars($article['excerpt']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger">
                                <span class="icon">🗑️</span> 确认删除
                            </button>
                            <a href="articles.php" class="btn btn-outline">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .article-preview {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .article-info {
            margin-bottom: 1rem;
        }
        
        .info-row {
            display: flex;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-row label {
            min-width: 100px;
            font-weight: 500;
            color: #6b7280;
        }
        
        .info-row span {
            color: #374151;
        }
        
        .article-excerpt {
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
        }
        
        .article-excerpt h4 {
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .article-excerpt p {
            color: #6b7280;
            line-height: 1.6;
        }
    </style>
</body>
</html>