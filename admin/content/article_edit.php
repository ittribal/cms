<?php
// ==================== admin/content/article_edit.php - 文章编辑 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);

$article_id = intval($_GET['id'] ?? 0);
if (!$article_id) {
    header('Location: articles.php');
    exit;
}

// 获取文章信息
$article = $db->fetchOne(
    "SELECT * FROM articles WHERE id = ?",
    [$article_id]
);

if (!$article) {
    set_flash_message('文章不存在', 'error');
    header('Location: articles.php');
    exit;
}

// 权限检查
if (!$auth->hasPermission('content.edit') && $article['author_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('权限不足');
}

// 获取分类列表
$categories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'CSRF验证失败';
    } else {
        $title = sanitize_input($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $excerpt = sanitize_input($_POST['excerpt'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $status = $_POST['status'] ?? 'draft';
        $meta_title = sanitize_input($_POST['meta_title'] ?? '');
        $meta_description = sanitize_input($_POST['meta_description'] ?? '');
        
        // 验证
        if (empty($title)) {
            $errors[] = '标题不能为空';
        }
        
        if (empty($content)) {
            $errors[] = '内容不能为空';
        }
        
        if (!in_array($status, ['draft', 'published', 'archived'])) {
            $status = 'draft';
        }
        
        // 生成slug
        $slug = generate_slug($title);
        
        // 检查slug唯一性
        $existing = $db->fetchOne(
            "SELECT id FROM articles WHERE slug = ? AND id != ?",
            [$slug, $article_id]
        );
        
        if ($existing) {
            $slug .= '-' . time();
        }
        
        if (empty($errors)) {
            try {
                $update_data = [
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'excerpt' => $excerpt,
                    'category_id' => $category_id ?: null,
                    'status' => $status,
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description
                ];
                
                // 如果状态改为已发布且之前未发布，设置发布时间
                if ($status === 'published' && $article['status'] !== 'published') {
                    $update_data['published_at'] = date('Y-m-d H:i:s');
                }
                
                $db->update('articles', $update_data, "id = {$article_id}");
                
                // 记录日志
                $auth->logAction($_SESSION['user_id'], 'article_update', 'articles', $article_id);
                
                set_flash_message('文章更新成功', 'success');
                header('Location: articles.php');
                exit;
            } catch (Exception $e) {
                $errors[] = '保存失败: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑文章 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/35.4.0/classic/ckeditor.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">编辑文章</h1>
                <div class="page-actions">
                    <a href="articles.php" class="btn btn-outline">
                        <span class="icon">←</span> 返回列表
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="article-form">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-layout">
                    <div class="main-content">
                        <div class="content-card">
                            <div class="form-group">
                                <label for="title">文章标题 *</label>
                                <input type="text" id="title" name="title" required 
                                       value="<?= htmlspecialchars($article['title']) ?>"
                                       class="form-control form-control-lg">
                            </div>
                            
                            <div class="form-group">
                                <label for="content">文章内容 *</label>
                                <textarea id="content" name="content" rows="15" 
                                          class="form-control"><?= htmlspecialchars($article['content']) ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="excerpt">文章摘要</label>
                                <textarea id="excerpt" name="excerpt" rows="3" 
                                          class="form-control" 
                                          placeholder="留空将自动从内容中提取..."><?= htmlspecialchars($article['excerpt']) ?></textarea>
                            </div>
                        </div>
                        
                        <!-- SEO设置 -->
                        <div class="content-card">
                            <h3 class="card-title">SEO 设置</h3>
                            <div class="form-group">
                                <label for="meta_title">SEO标题</label>
                                <input type="text" id="meta_title" name="meta_title" 
                                       value="<?= htmlspecialchars($article['meta_title']) ?>"
                                       class="form-control">
                                <small class="form-help">建议长度：50-60个字符</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">SEO描述</label>
                                <textarea id="meta_description" name="meta_description" rows="3" 
                                          class="form-control"><?= htmlspecialchars($article['meta_description']) ?></textarea>
                                <small class="form-help">建议长度：150-160个字符</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-content">
                        <!-- 发布设置 -->
                        <div class="content-card">
                            <h3 class="card-title">发布设置</h3>
                            
                            <div class="form-group">
                                <label for="status">状态</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>草稿</option>
                                    <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>发布</option>
                                    <option value="archived" <?= $article['status'] === 'archived' ? 'selected' : '' ?>>归档</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">分类</label>
                                <select id="category_id" name="category_id" class="form-control">
                                    <option value="">请选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= $article['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-block">
                                    保存更改
                                </button>
                                <a href="articles.php" class="btn btn-outline btn-block">取消</a>
                            </div>
                        </div>
                        
                        <!-- 文章信息 -->
                        <div class="content-card">
                            <h3 class="card-title">文章信息</h3>
                            <div class="info-item">
                                <label>创建时间:</label>
                                <span><?= date('Y-m-d H:i:s', strtotime($article['created_at'])) ?></span>
                            </div>
                            <div class="info-item">
                                <label>更新时间:</label>
                                <span><?= date('Y-m-d H:i:s', strtotime($article['updated_at'])) ?></span>
                            </div>
                            <div class="info-item">
                                <label>浏览次数:</label>
                                <span><?= number_format($article['views']) ?></span>
                            </div>
                            <?php if ($article['published_at']): ?>
                                <div class="info-item">
                                    <label>发布时间:</label>
                                    <span><?= date('Y-m-d H:i:s', strtotime($article['published_at'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        // 初始化富文本编辑器
        ClassicEditor
            .create(document.querySelector('#content'), {
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'bulletedList', 'numberedList', '|',
                    'link', 'blockQuote', 'insertTable', '|',
                    'undo', 'redo'
                ],
                heading: {
                    options: [
                        { model: 'paragraph', title: '段落', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: '标题 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: '标题 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: '标题 3', class: 'ck-heading_heading3' }
                    ]
                }
            })
            .catch(error => {
                console.error(error);
            });
    </script>
    
    <style>
        .article-form {
            max-width: none;
        }
        
        .form-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }
        
        .main-content,
        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-control-lg {
            padding: 12px 16px;
            font-size: 18px;
            font-weight: 500;
        }
        
        .form-help {
            display: block;
            margin-top: 0.25rem;
            font-size: 12px;
            color: #6b7280;
        }
        
        .form-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-block {
            width: 100%;
            justify-content: center;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item label {
            font-weight: 500;
            color: #6b7280;
            margin: 0;
        }
        
        .info-item span {
            color: #374151;
            font-size: 14px;
        }
        
        .ck-editor__editable {
            min-height: 400px;
        }
        
        @media (max-width: 1024px) {
            .form-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
