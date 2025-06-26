<?php
// admin/article_edit.php - 编辑文章页面
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

if (!$auth->hasPermission('article.edit')) {
    die('您没有权限编辑文章');
}

$pageTitle = '编辑文章';
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
    SELECT a.*, c.name as category_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.id = ?
", [$articleId]);

if (!$article) {
    header('Location: articles.php?error=' . urlencode('文章不存在'));
    exit;
}

// 检查编辑权限（只能编辑自己的文章，除非是管理员）
if ($article['author_id'] != $currentUser['id'] && !$auth->hasPermission('article.manage')) {
    header('Location: articles.php?error=' . urlencode('您只能编辑自己的文章'));
    exit;
}

// 获取文章标签
$articleTags = $db->fetchAll("
    SELECT t.name 
    FROM tags t 
    JOIN article_tags at ON t.id = at.tag_id 
    WHERE at.article_id = ?
", [$articleId]);

$tagsString = implode(', ', array_column($articleTags, 'name'));

// 处理表单提交
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if (in_array($action, ['save_draft', 'publish', 'update'])) {
        $result = handleArticleUpdate($_POST, $action, $articleId);
        if ($result['success']) {
            $message = $result['message'];
            if ($action === 'publish') {
                header('Location: articles.php?message=' . urlencode($message));
                exit;
            }
            // 重新加载文章数据
            $article = $db->fetchOne("
                SELECT a.*, c.name as category_name 
                FROM articles a 
                LEFT JOIN categories c ON a.category_id = c.id 
                WHERE a.id = ?
            ", [$articleId]);
        } else {
            $error = $result['message'];
        }
    }
}

// 处理文章更新
function handleArticleUpdate($data, $action, $articleId) {
    global $db, $auth;
    
    try {
        // 验证必填字段
        if (empty($data['title'])) {
            return ['success' => false, 'message' => '文章标题不能为空'];
        }
        
        if (empty($data['content'])) {
            return ['success' => false, 'message' => '文章内容不能为空'];
        }
        
        // 生成URL别名
        $slug = !empty($data['slug']) ? $data['slug'] : generateSlug($data['title']);
        
        // 检查别名是否重复（排除当前文章）
        $existing = $db->fetchOne("SELECT id FROM articles WHERE slug = ? AND id != ?", [$slug, $articleId]);
        if ($existing) {
            $slug .= '-' . time();
        }
        
        // 处理特色图片
        $featuredImage = $data['current_featured_image'] ?? '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            // 删除旧图片
            if ($featuredImage && file_exists('../' . $featuredImage)) {
                unlink('../' . $featuredImage);
            }
            $featuredImage = uploadFeaturedImage($_FILES['featured_image']);
        }
        
        // 确定文章状态
        $newStatus = '';
        switch ($action) {
            case 'publish':
                $newStatus = 'published';
                break;
            case 'save_draft':
                $newStatus = 'draft';
                break;
            case 'update':
                // 保持原状态，除非明确指定
                $newStatus = $data['status'] ?? $data['current_status'] ?? 'draft';
                break;
        }
        
        // 构建更新数据
        $updateData = [
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?: generateExcerpt($data['content']),
            'category_id' => !empty($data['category_id']) ? $data['category_id'] : null,
            'status' => $newStatus,
            'featured_image' => $featuredImage,
            'meta_title' => $data['meta_title'] ?: '',
            'meta_description' => $data['meta_description'] ?: '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($action === 'publish' && $newStatus === 'published') {
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }
        
        // 构建SQL语句
        $setClause = implode(', ', array_map(function($key) {
            return "$key = ?";
        }, array_keys($updateData)));
        
        $sql = "UPDATE articles SET $setClause WHERE id = ?";
        $params = array_merge(array_values($updateData), [$articleId]);
        
        if ($db->execute($sql, $params)) {
            // 更新标签
            // 先删除现有标签关联
            $db->execute("DELETE FROM article_tags WHERE article_id = ?", [$articleId]);
            
            // 添加新标签
            if (!empty($data['tags'])) {
                saveTags($articleId, $data['tags']);
            }
            
            $auth->logAction('更新文章', '文章ID: ' . $articleId . ', 操作: ' . $action);
            
            $statusMessages = [
                'publish' => '文章发布成功！',
                'save_draft' => '草稿保存成功！',
                'update' => '文章更新成功！'
            ];
            
            return [
                'success' => true, 
                'message' => $statusMessages[$action] ?? '操作成功！',
                'article_id' => $articleId
            ];
        }
        
        return ['success' => false, 'message' => '更新失败，请重试'];
        
    } catch (Exception $e) {
        error_log('Article update error: ' . $e->getMessage());
        return ['success' => false, 'message' => '操作失败：' . $e->getMessage()];
    }
}

// 生成URL别名
function generateSlug($title) {
    $slug = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $title);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return strtolower($slug) ?: 'article-' . time();
}

// 生成摘要
function generateExcerpt($content, $length = 200) {
    $text = strip_tags($content);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}

// 上传特色图片
function uploadFeaturedImage($file) {
    $uploadDir = '../uploads/images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('不支持的图片格式');
    }
    
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('图片大小不能超过5MB');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . rand(1000, 9999) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/images/' . $filename;
    }
    
    throw new Exception('图片上传失败');
}

// 保存标签
function saveTags($articleId, $tagsString) {
    global $db;
    
    $tags = array_filter(array_map('trim', explode(',', $tagsString)));
    
    foreach ($tags as $tagName) {
        // 检查标签是否存在
        $tag = $db->fetchOne("SELECT id FROM tags WHERE name = ?", [$tagName]);
        
        if (!$tag) {
            // 创建新标签
            $tagSlug = generateSlug($tagName);
            $db->execute(
                "INSERT INTO tags (name, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
                [$tagName, $tagSlug]
            );
            $tagId = $db->getLastInsertId();
        } else {
            $tagId = $tag['id'];
        }
        
        // 关联文章和标签
        $db->execute(
            "INSERT IGNORE INTO article_tags (article_id, tag_id, created_at) VALUES (?, ?, NOW())",
            [$articleId, $tagId]
        );
    }
}

// 获取分类列表
$categories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC");

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-edit"></i> 编辑文章</h1>
                <p>修改文章内容，支持富文本编辑和图片上传</p>
                <div class="article-info">
                    <span class="info-badge status-<?php echo $article['status']; ?>">
                        <?php 
                        $statusMap = [
                            'draft' => '草稿',
                            'published' => '已发布',
                            'archived' => '已归档'
                        ];
                        echo $statusMap[$article['status']] ?? $article['status'];
                        ?>
                    </span>
                    <span class="info-badge">ID: <?php echo $article['id']; ?></span>
                    <span class="info-badge">作者: <?php echo htmlspecialchars($article['author_id']); ?></span>
                </div>
            </div>
            <div class="header-actions">
                <a href="articles.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
                <button type="button" onclick="previewArticle()" class="btn btn-info">
                    <i class="fas fa-eye"></i> 预览
                </button>
                <a href="article_view.php?id=<?php echo $article['id']; ?>" class="btn btn-success" target="_blank">
                    <i class="fas fa-external-link-alt"></i> 查看
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- 文章编辑表单 -->
        <form id="articleForm" method="POST" enctype="multipart/form-data" class="article-editor">
            <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($article['status']); ?>">
            <input type="hidden" name="current_featured_image" value="<?php echo htmlspecialchars($article['featured_image']); ?>">
            
            <div class="editor-layout">
                <!-- 主编辑区域 -->
                <div class="editor-main">
                    <!-- 文章标题 -->
                    <div class="content-card">
                        <div class="card-body">
                            <div class="form-group">
                                <input type="text" name="title" id="articleTitle" class="form-control title-input" 
                                       placeholder="请输入文章标题..." maxlength="255" required
                                       value="<?php echo htmlspecialchars($article['title']); ?>">
                                <div class="title-counter">
                                    <span id="titleCount"><?php echo mb_strlen($article['title']); ?></span>/255
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="slug">URL别名</label>
                                <input type="text" name="slug" id="articleSlug" class="form-control" 
                                       placeholder="URL别名"
                                       value="<?php echo htmlspecialchars($article['slug']); ?>">
                                <small class="form-text">用于生成文章链接，建议使用英文</small>
                            </div>
                        </div>
                    </div>

                    <!-- Quill.js 编辑器 -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-edit"></i> 文章内容</h3>
                            <div class="editor-tools">
                                <span class="editor-tips">
                                    <i class="fas fa-info-circle"></i>
                                    支持复制粘贴图片、拖拽上传、表格、代码块等
                                </span>
                            </div>
                        </div>
                        <div class="card-body editor-container">
                            <!-- Quill.js 编辑器容器 -->
                            <div id="quillEditor" class="quill-editor"></div>
                            <textarea name="content" id="contentHidden" style="display: none;" required><?php echo htmlspecialchars($article['content']); ?></textarea>
                            
                            <!-- 编辑器状态栏 -->
                            <div class="editor-status">
                                <span class="word-count">字数: <span id="wordCount">0</span></span>
                                <span class="character-count">字符: <span id="charCount">0</span></span>
                                <span class="last-saved">最后保存: <span id="lastSaved">
                                    <?php echo date('Y-m-d H:i:s', strtotime($article['updated_at'])); ?>
                                </span></span>
                            </div>
                        </div>
                    </div>

                    <!-- 文章摘要 -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-align-left"></i> 文章摘要</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <textarea name="excerpt" id="articleExcerpt" class="form-control" rows="3" 
                                          placeholder="文章摘要，用于在列表页显示。留空将自动生成..." maxlength="500"><?php echo htmlspecialchars($article['excerpt']); ?></textarea>
                                <div class="form-text">
                                    推荐长度：120-200字符 
                                    <span class="excerpt-counter">
                                        当前: <span id="excerptCount"><?php echo mb_strlen($article['excerpt']); ?></span>/500
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 侧边栏设置 -->
                <div class="editor-sidebar">
                    <!-- 发布设置 -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-paper-plane"></i> 发布设置</h3>
                        </div>
                        <div class="card-body">
                            <div class="publish-actions">
                                <button type="submit" name="action" value="update" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> 更新文章
                                </button>
                                <?php if ($article['status'] !== 'published'): ?>
                                <button type="submit" name="action" value="publish" class="btn btn-success btn-block">
                                    <i class="fas fa-paper-plane"></i> 立即发布
                                </button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="save_draft" class="btn btn-secondary btn-block">
                                    <i class="fas fa-file-alt"></i> 保存为草稿
                                </button>
                            </div>
                            
                            <div class="publish-info">
                                <div class="info-item">
                                    <label>当前状态:</label>
                                    <span class="status-<?php echo $article['status']; ?>">
                                        <?php echo $statusMap[$article['status']] ?? $article['status']; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <label>创建时间:</label>
                                    <span><?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>更新时间:</label>
                                    <span><?php echo date('Y-m-d H:i', strtotime($article['updated_at'])); ?></span>
                                </div>
                                <?php if ($article['published_at']): ?>
                                <div class="info-item">
                                    <label>发布时间:</label>
                                    <span><?php echo date('Y-m-d H:i', strtotime($article['published_at'])); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="info-item">
                                    <label>浏览量:</label>
                                    <span><?php echo number_format($article['views']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 分类设置 -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-folder"></i> 分类设置</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <select name="category_id" id="categorySelect" class="form-control">
                                    <option value="">选择分类...</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo ($category['id'] == $article['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 标签设置 -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-tags"></i> 标签设置</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <input type="text" name="tags" id="articleTags" class="form-control" 
                                       placeholder="输入标签，用逗号分隔..."
                                       value="<?php echo htmlspecialchars($tagsString); ?>">
                                <small class="form-text">例如：技术, PHP, 教程</small>
                            </div>
                            <div class="popular-tags">
                                <label>热门标签:</label>
                                <div class="tag-suggestions" id="tagSuggestions">
                                    <!-- 标签建议将通过JavaScript加载 -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 特色图片 -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-image"></i> 特色图片</h3>
                        </div>
                        <div class="card-body">
                            <div class="image-upload-area" id="imageUploadArea">
                                <?php if ($article['featured_image']): ?>
                                <div class="image-preview" id="imagePreview">
                                    <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="特色图片" id="previewImg">
                                    <button type="button" class="remove-image" onclick="removeImage()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="upload-placeholder" style="display: none;">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>点击或拖拽上传图片</p>
                                    <small>支持 JPG, PNG, GIF, WebP 格式<br>最大 5MB</small>
                                </div>
                                <?php else: ?>
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>点击或拖拽上传图片</p>
                                    <small>支持 JPG, PNG, GIF, WebP 格式<br>最大 5MB</small>
                                </div>
                                <div class="image-preview" id="imagePreview" style="display: none;">
                                    <img src="" alt="预览" id="previewImg">
                                    <button type="button" class="remove-image" onclick="removeImage()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                                <input type="file" name="featured_image" id="featuredImage" 
                                       accept="image/*" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <!-- SEO设置 -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-search"></i> SEO设置</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="metaTitle">SEO标题</label>
                                <input type="text" name="meta_title" id="metaTitle" class="form-control" 
                                       placeholder="搜索引擎显示的标题" maxlength="60"
                                       value="<?php echo htmlspecialchars($article['meta_title']); ?>">
                                <div class="seo-counter">
                                    <span id="metaTitleCount"><?php echo mb_strlen($article['meta_title']); ?></span>/60
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="metaDescription">SEO描述</label>
                                <textarea name="meta_description" id="metaDescription" class="form-control" 
                                          rows="3" placeholder="搜索引擎显示的描述" maxlength="160"><?php echo htmlspecialchars($article['meta_description']); ?></textarea>
                                <div class="seo-counter">
                                    <span id="metaDescCount"><?php echo mb_strlen($article['meta_description']); ?></span>/160
                                </div>
                            </div>
                            
                            <div class="seo-preview">
                                <h4>搜索结果预览:</h4>
                                <div class="search-preview">
                                    <div class="preview-title" id="previewTitle"><?php echo htmlspecialchars($article['meta_title'] ?: $article['title']); ?></div>
                                    <div class="preview-url">https://yoursite.com/article/<?php echo htmlspecialchars($article['slug']); ?></div>
                                    <div class="preview-description" id="previewDescription"><?php echo htmlspecialchars($article['meta_description'] ?: $article['excerpt']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<style>
/* 编辑页面特有样式 */
.article-info {
    margin-top: 0.5rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.info-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #e9ecef;
    color: #495057;
    border-radius: 3px;
    font-size: 0.75rem;
    font-weight: 500;
}

.info-badge.status-draft {
    background: #fff3cd;
    color: #856404;
}

.info-badge.status-published {
    background: #d4edda;
    color: #155724;
}

.info-badge.status-archived {
    background: #f8d7da;
    color: #721c24;
}

.status-draft {
    color: #f39c12;
    font-weight: 500;
}

.status-published {
    color: #27ae60;
    font-weight: 500;
}

.status-archived {
    color: #e74c3c;
    font-weight: 500;
}

/* 继承之前的样式 */
.article-editor {
    margin-bottom: 2rem;
}

.editor-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
}

.editor-main {
    min-width: 0;
}

.editor-sidebar {
    min-width: 0;
}

.content-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    background: #fafbfc;
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.editor-tools {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.editor-tips {
    font-size: 0.8rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* 标题输入框 */
.title-input {
    font-size: 1.5rem;
    font-weight: 600;
    border: none;
    outline: none;
    padding: 1rem 0;
    background: transparent;
    color: #2c3e50;
    width: 100%;
}

.title-input:focus {
    border-bottom: 2px solid #3498db;
}

.title-counter {
    text-align: right;
    font-size: 0.8rem;
    color: #7f8c8d;
    margin-top: 0.5rem;
}

/* Quill.js 编辑器容器 */
.editor-container {
    padding: 0;
}

.quill-editor {
    min-height: 500px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}

/* Quill.js 自定义样式 */
.ql-toolbar {
    border-radius: 8px 8px 0 0;
    background: #f8f9fa;
    border-color: #ddd;
    padding: 0.75rem;
}

.ql-container {
    border-radius: 0 0 8px 8px;
    border-color: #ddd;
    font-size: 1rem;
    line-height: 1.7;
}

.ql-editor {
    min-height: 450px;
    padding: 2rem;
    color: #2c3e50;
}

.ql-editor.ql-blank::before {
    color: #adb5bd;
    content: attr(data-placeholder);
    font-style: italic;
    left: 2rem;
    pointer-events: none;
    position: absolute;
    right: 2rem;
}

/* 编辑器状态栏 */
.editor-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #eee;
    font-size: 0.85rem;
    color: #6c757d;
}

.editor-status span {
    margin-right: 1rem;
}

/* 侧边栏样式 */
.editor-sidebar .content-card {
    margin-bottom: 1.5rem;
}

.editor-sidebar .card-body {
    padding: 1rem;
}

/* 发布设置 */
.publish-actions {
    margin-bottom: 1rem;
}

.publish-actions .btn {
    margin-bottom: 0.5rem;
}

.btn-block {
    width: 100%;
    justify-content: center;
}

.btn {
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    background: none;
    font-size: 0.9rem;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border-color: #2980b9;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border-color: #6c757d;
}

.btn-secondary:hover {
    background: #545b62;
    text-decoration: none;
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #27ae60, #219a52);
    color: white;
    border-color: #219a52;
}

.btn-success:hover {
    background: linear-gradient(135deg, #219a52, #1e8449);
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

.publish-info {
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

.info-item label {
    font-weight: 600;
    color: #495057;
}

/* 表单控件 */
.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.form-text {
    color: #6c757d;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

/* 图片上传区域 */
.image-upload-area {
    position: relative;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.image-upload-area:hover {
    border-color: #3498db;
    background: #f8f9fa;
}

.upload-placeholder i {
    font-size: 2rem;
    color: #adb5bd;
    margin-bottom: 1rem;
}

.upload-placeholder p {
    margin: 0.5rem 0;
    color: #495057;
    font-weight: 500;
}

.upload-placeholder small {
    color: #6c757d;
}

.image-preview {
    position: relative;
}

.image-preview img {
    max-width: 100%;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.remove-image {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 28px;
    height: 28px;
    border: none;
    background: rgba(231, 76, 60, 0.9);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
}

.remove-image:hover {
    background: rgba(231, 76, 60, 1);
}

/* SEO设置 */
.seo-counter {
    text-align: right;
    font-size: 0.8rem;
    color: #7f8c8d;
    margin-top: 0.25rem;
}

.seo-preview {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.seo-preview h4 {
    font-size: 0.9rem;
    color: #495057;
    margin-bottom: 0.75rem;
}

.search-preview {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 5px;
    border-left: 3px solid #3498db;
}

.preview-title {
    color: #1a0dab;
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    cursor: pointer;
}

.preview-url {
    color: #006621;
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}

.preview-description {
    color: #545454;
    font-size: 0.85rem;
    line-height: 1.4;
}

/* 标签设置 */
.popular-tags {
    margin-top: 1rem;
}

.popular-tags label {
    font-size: 0.85rem;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.tag-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag-suggestion {
    background: #e9ecef;
    color: #495057;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tag-suggestion:hover {
    background: #3498db;
    color: white;
}

/* 字数统计样式 */
.excerpt-counter,
.title-counter,
.seo-counter {
    font-size: 0.8rem;
    color: #7f8c8d;
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
    color: #2c3e50;
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

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left-color: #27ae60;
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
@media (max-width: 1024px) {
    .editor-layout {
        grid-template-columns: 1fr;
    }
    
    .editor-sidebar {
        order: -1;
    }
}

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
    
    .title-input {
        font-size: 1.2rem;
    }
    
    .ql-editor {
        min-height: 300px;
        padding: 1rem;
    }
    
    .article-info {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<!-- 加载 Quill.js 编辑器 -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
console.log('Quill.js 编辑器开始加载（编辑模式）...');

let quillEditor;

// 等待DOM加载完成
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM 加载完成，初始化 Quill.js 编辑器...');
    
    // 初始化 Quill.js 编辑器
    initializeQuillEditor();
    
    // 绑定其他事件
    bindEvents();
    
    // 加载热门标签
    loadPopularTags();
    
    // 启动自动保存
    startAutoSave();
    
    // 初始化计数器
    updateAllCounters();
});

// 初始化 Quill.js 编辑器
function initializeQuillEditor() {
    try {
        // 创建工具栏配置
        const toolbarOptions = [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'font': [] }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'align': [] }],
            ['blockquote', 'code-block'],
            ['link', 'image', 'video'],
            ['clean']
        ];

        // 初始化 Quill 编辑器
        quillEditor = new Quill('#quillEditor', {
            theme: 'snow',
            placeholder: '编辑你的文章内容...',
            modules: {
                toolbar: {
                    container: toolbarOptions,
                    handlers: {
                        image: imageHandler
                    }
                }
            }
        });

        console.log('Quill.js 编辑器初始化完成');

        // 加载现有内容
        const existingContent = document.getElementById('contentHidden').value;
        if (existingContent) {
            quillEditor.root.innerHTML = existingContent;
        }

        // 监听内容变化
        quillEditor.on('text-change', function(delta, oldDelta, source) {
            updateHiddenTextarea();
            updateWordCount();
            updateSEOPreview();
        });

        // 设置图片拖拽和粘贴处理
        setupImageHandling();

        console.log('Quill.js 编辑器配置完成');

    } catch (error) {
        console.error('Quill.js 编辑器初始化失败:', error);
        showEditorError('编辑器初始化失败：' + error.message);
    }
}

// 图片处理器
function imageHandler() {
    console.log('图片工具栏按钮点击');
    
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = function() {
        const file = input.files[0];
        if (file) {
            console.log('选择图片文件:', file.name);
            uploadImageToEditor(file);
        }
    };
}

// 设置图片处理
function setupImageHandling() {
    const editor = quillEditor.root;

    // 处理粘贴事件
    editor.addEventListener('paste', function(e) {
        console.log('检测到粘贴事件');
        
        const clipboardData = e.clipboardData || window.clipboardData;
        const items = clipboardData.items;

        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            if (item.type.indexOf('image') !== -1) {
                console.log('粘贴板中发现图片');
                e.preventDefault();
                
                const file = item.getAsFile();
                uploadImageToEditor(file);
                break;
            }
        }
    });

    // 处理拖拽事件
    editor.addEventListener('drop', function(e) {
        console.log('检测到拖拽事件');
        e.preventDefault();

        const files = e.dataTransfer.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.type.startsWith('image/')) {
                console.log('拖拽图片文件:', file.name);
                uploadImageToEditor(file);
                break;
            }
        }
    });

    // 防止默认拖拽行为
    editor.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
}

// 上传图片到编辑器
function uploadImageToEditor(file) {
    console.log('开始上传图片:', file.name);
    
    // 验证文件类型
    if (!file.type.startsWith('image/')) {
        showToast('请选择图片文件', 'error');
        return;
    }
    
    // 验证文件大小 (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showToast('图片大小不能超过5MB', 'error');
        return;
    }
    
    // 显示上传进度
    showToast('正在上传图片...', 'info', 0);
    
    const formData = new FormData();
    formData.append('upload', file);
    
    fetch('upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        hideToast();
        
        if (result.success) {
            console.log('图片上传成功:', result.url);
            
            // 获取当前光标位置
            const range = quillEditor.getSelection(true);
            
            // 插入图片
            quillEditor.insertEmbed(range.index, 'image', result.url);
            
            // 移动光标到图片后面
            quillEditor.setSelection(range.index + 1);
            
            showToast('图片上传成功', 'success');
        } else {
            console.error('图片上传失败:', result.message);
            showToast('图片上传失败：' + result.message, 'error');
        }
    })
    .catch(error => {
        console.error('图片上传异常:', error);
        hideToast();
        showToast('图片上传异常：' + error.message, 'error');
    });
}

// 更新隐藏的textarea
function updateHiddenTextarea() {
    if (quillEditor) {
        const html = quillEditor.root.innerHTML;
        document.getElementById('contentHidden').value = html;
    }
}

// 更新字数统计
function updateWordCount() {
    if (quillEditor) {
        const text = quillEditor.getText();
        const words = text.trim().split(/\s+/).filter(word => word.length > 0).length;
        const characters = text.length;
        
        const wordCountEl = document.getElementById('wordCount');
        const charCountEl = document.getElementById('charCount');
        
        if (wordCountEl) wordCountEl.textContent = words;
        if (charCountEl) charCountEl.textContent = characters;
    }
}

// 更新所有计数器
function updateAllCounters() {
    updateTitleCounter();
    updateExcerptCounter();
    updateMetaTitleCounter();
    updateMetaDescCounter();
    updateWordCount();
    updateSEOPreview();
}

// 绑定其他事件
function bindEvents() {
    console.log('绑定事件...');
    
    // 标题输入事件
    const titleInput = document.getElementById('articleTitle');
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            updateTitleCounter();
            updateSlug();
            updateSEOPreview();
        });
    }
    
    // 摘要输入事件
    const excerptTextarea = document.getElementById('articleExcerpt');
    if (excerptTextarea) {
        excerptTextarea.addEventListener('input', function() {
            updateExcerptCounter();
            updateSEOPreview();
        });
    }
    
    // SEO字段事件
    const metaTitle = document.getElementById('metaTitle');
    const metaDescription = document.getElementById('metaDescription');
    
    if (metaTitle) {
        metaTitle.addEventListener('input', function() {
            updateMetaTitleCounter();
            updateSEOPreview();
        });
    }
    
    if (metaDescription) {
        metaDescription.addEventListener('input', function() {
            updateMetaDescCounter();
            updateSEOPreview();
        });
    }
    
    // 图片上传事件
    bindImageUploadEvents();
    
    // 表单提交事件
    bindFormSubmitEvents();
}

// 绑定表单提交事件
function bindFormSubmitEvents() {
    const form = document.getElementById('articleForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('表单提交事件触发');
            
            // 更新隐藏字段
            updateHiddenTextarea();
            
            // 验证表单
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // 显示提交状态
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
            });
        });
    }
}

// 绑定图片上传事件
function bindImageUploadEvents() {
    const imageUpload = document.getElementById('featuredImage');
    const uploadArea = document.getElementById('imageUploadArea');
    
    if (uploadArea && imageUpload) {
        uploadArea.addEventListener('click', () => imageUpload.click());
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#3498db';
            uploadArea.style.background = '#f8f9fa';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#dee2e6';
            uploadArea.style.background = 'transparent';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#dee2e6';
            uploadArea.style.background = 'transparent';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleImageUpload(files[0]);
            }
        });
        
        imageUpload.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleImageUpload(e.target.files[0]);
            }
        });
    }
}

// 更新标题计数器
function updateTitleCounter() {
    const titleInput = document.getElementById('articleTitle');
    const counter = document.getElementById('titleCount');
    
    if (titleInput && counter) {
        counter.textContent = titleInput.value.length;
    }
}

// 更新摘要计数器
function updateExcerptCounter() {
    const excerptTextarea = document.getElementById('articleExcerpt');
    const counter = document.getElementById('excerptCount');
    
    if (excerptTextarea && counter) {
        counter.textContent = excerptTextarea.value.length;
    }
}

// 更新SEO标题计数器
function updateMetaTitleCounter() {
    const metaTitle = document.getElementById('metaTitle');
    const counter = document.getElementById('metaTitleCount');
    
    if (metaTitle && counter) {
        counter.textContent = metaTitle.value.length;
    }
}

// 更新SEO描述计数器
function updateMetaDescCounter() {
    const metaDescription = document.getElementById('metaDescription');
    const counter = document.getElementById('metaDescCount');
    
    if (metaDescription && counter) {
        counter.textContent = metaDescription.value.length;
    }
}

// 更新URL别名
function updateSlug() {
    // 编辑页面不自动更新slug，避免破坏现有链接
    // 用户需要手动修改
}

// 更新SEO预览
function updateSEOPreview() {
    const title = document.getElementById('articleTitle').value;
    const metaTitle = document.getElementById('metaTitle').value;
    const metaDescription = document.getElementById('metaDescription').value;
    const excerpt = document.getElementById('articleExcerpt').value;
    const slug = document.getElementById('articleSlug').value;
    
    const previewTitle = document.getElementById('previewTitle');
    const previewDescription = document.getElementById('previewDescription');
    
    if (previewTitle) {
        previewTitle.textContent = metaTitle || title || '文章标题';
    }
    
    if (previewDescription) {
        previewDescription.textContent = metaDescription || excerpt || '文章描述...';
    }
    
    // 更新URL预览
    const previewUrl = document.querySelector('.preview-url');
    if (previewUrl) {
        const baseUrl = 'https://yoursite.com/article/';
        previewUrl.textContent = baseUrl + (slug || 'url-slug');
    }
}

// 处理图片上传
function handleImageUpload(file) {
    console.log('处理图片上传:', file.name);
    
    if (!file.type.startsWith('image/')) {
        alert('请选择图片文件');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        alert('图片大小不能超过5MB');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('previewImg');
        const preview = document.getElementById('imagePreview');
        const placeholder = document.querySelector('.upload-placeholder');
        
        if (img && preview && placeholder) {
            img.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
    };
    reader.readAsDataURL(file);
}

// 移除图片
function removeImage() {
    console.log('移除图片');
    
    const preview = document.getElementById('imagePreview');
    const placeholder = document.querySelector('.upload-placeholder');
    const fileInput = document.getElementById('featuredImage');
    
    if (preview && placeholder && fileInput) {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
        fileInput.value = '';
        
        // 清空隐藏字段中的当前图片
        const hiddenInput = document.querySelector('input[name="current_featured_image"]');
        if (hiddenInput) {
            hiddenInput.value = '';
        }
    }
}

// 加载热门标签
function loadPopularTags() {
    const popularTags = ['技术', 'PHP', 'JavaScript', 'CSS', 'HTML', '前端', '后端', '数据库', '教程', '框架'];
    const container = document.getElementById('tagSuggestions');
    
    if (container) {
        container.innerHTML = '';
        popularTags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'tag-suggestion';
            span.textContent = tag;
            span.onclick = () => addTag(tag);
            container.appendChild(span);
        });
    }
}

// 添加标签
function addTag(tag) {
    const tagsInput = document.getElementById('articleTags');
    if (tagsInput) {
        const currentTags = tagsInput.value.split(',').map(t => t.trim()).filter(t => t);
        
        if (!currentTags.includes(tag)) {
            currentTags.push(tag);
            tagsInput.value = currentTags.join(', ');
        }
    }
}

// 验证表单
function validateForm() {
    const title = document.getElementById('articleTitle').value.trim();
    const content = quillEditor ? quillEditor.getText().trim() : '';
    
    if (!title) {
        alert('请输入文章标题');
        document.getElementById('articleTitle').focus();
        return false;
    }
    
    if (!content) {
        alert('请输入文章内容');
        if (quillEditor) {
            quillEditor.focus();
        }
        return false;
    }
    
    return true;
}

// 预览文章
function previewArticle() {
    console.log('预览文章');
    
    const title = document.getElementById('articleTitle').value.trim();
    const content = quillEditor ? quillEditor.root.innerHTML : '';
    
    if (!title || !content) {
        alert('请先输入标题和内容');
        return;
    }
    
    // 打开新窗口显示预览
    const previewWindow = window.open('', '_blank');
    const previewHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>预览 - ${title}</title>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                    max-width: 800px; 
                    margin: 2rem auto; 
                    padding: 2rem; 
                    line-height: 1.6; 
                    color: #333;
                }
                h1 { 
                    color: #2c3e50; 
                    border-bottom: 2px solid #3498db; 
                    padding-bottom: 0.5rem; 
                }
                h2, h3, h4, h5, h6 { 
                    color: #2c3e50; 
                    margin-top: 2rem; 
                }
                img { 
                    max-width: 100%; 
                    height: auto; 
                    border-radius: 5px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                blockquote { 
                    border-left: 4px solid #3498db; 
                    margin: 1.5rem 0; 
                    padding: 1rem 1.5rem; 
                    background: #f8f9fa; 
                    font-style: italic;
                }
                .ql-align-center { text-align: center; }
                .ql-align-right { text-align: right; }
                .ql-align-justify { text-align: justify; }
                pre { 
                    background: #2c3e50; 
                    color: #ecf0f1; 
                    padding: 1rem; 
                    border-radius: 5px; 
                    overflow-x: auto; 
                    font-family: 'Courier New', monospace;
                }
            </style>
        </head>
        <body>
            <h1>${title}</h1>
            <div class="content">${content}</div>
        </body>
        </html>
    `;
    
    previewWindow.document.write(previewHTML);
    previewWindow.document.close();
}

// 自动保存功能
let autoSaveTimer;
function startAutoSave() {
    console.log('启动自动保存');
    
    // 每60秒自动保存一次
    autoSaveTimer = setInterval(function() {
        const title = document.getElementById('articleTitle').value.trim();
        const content = quillEditor ? quillEditor.getText().trim() : '';
        
        if (title && content) {
            console.log('执行自动保存');
            autoSaveArticle();
        }
    }, 60000); // 60秒
}

// 自动保存文章
function autoSaveArticle() {
    console.log('自动保存文章');
    
    showToast('正在自动保存...', 'info');
    
    updateHiddenTextarea();
    
    const formData = new FormData(document.getElementById('articleForm'));
    formData.set('action', 'update');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            showToast('已自动保存', 'success');
            const lastSavedEl = document.getElementById('lastSaved');
            if (lastSavedEl) {
                lastSavedEl.textContent = new Date().toLocaleTimeString();
            }
        } else {
            throw new Error('保存失败');
        }
    })
    .catch(error => {
        showToast('自动保存失败', 'error');
        console.error('自动保存失败:', error);
    });
}

// 显示编辑器加载错误
function showEditorError(message) {
    const editorContainer = document.querySelector('.quill-editor');
    if (editorContainer) {
        editorContainer.innerHTML = `
            <div style="padding: 2rem; text-align: center; color: #e74c3c; border: 2px dashed #e74c3c; border-radius: 8px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3>编辑器加载失败</h3>
                <p>${message}</p>
                <button onclick="location.reload()" style="padding: 0.5rem 1rem; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    重新加载
                </button>
            </div>
        `;
    }
}

// 显示提示消息
function showToast(message, type = 'info', duration = 3000) {
    let toast = document.getElementById('toast');
    
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        document.body.appendChild(toast);
    }
    
    // 清除之前的定时器
    if (toast.timeoutId) {
        clearTimeout(toast.timeoutId);
    }
    
    // 设置样式和图标
    let icon = '';
    switch (type) {
        case 'success':
            toast.style.background = '#27ae60';
            icon = '<i class="fas fa-check-circle"></i>';
            break;
        case 'error':
            toast.style.background = '#e74c3c';
            icon = '<i class="fas fa-exclamation-circle"></i>';
            break;
        case 'info':
            toast.style.background = '#3498db';
            icon = '<i class="fas fa-info-circle"></i>';
            break;
        default:
            toast.style.background = '#6c757d';
            icon = '<i class="fas fa-bell"></i>';
    }
    
    toast.innerHTML = icon + ' ' + message;
    toast.style.opacity = '1';
    
    if (duration > 0) {
        toast.timeoutId = setTimeout(() => {
            toast.style.opacity = '0';
        }, duration);
    }
}

// 隐藏提示消息
function hideToast() {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.style.opacity = '0';
    }
}

// 页面离开前提醒保存
window.addEventListener('beforeunload', function(e) {
    // 编辑页面通常有未保存的更改，可以根据需要启用
    // e.preventDefault();
    // e.returnValue = '您有未保存的更改，确定要离开吗？';
    // return e.returnValue;
});

// 键盘快捷键
document.addEventListener('keydown', function(e) {
    // Ctrl+S 保存
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.querySelector('button[value="update"]').click();
    }
    
    // Ctrl+Enter 发布文章
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        const publishBtn = document.querySelector('button[value="publish"]');
        if (publishBtn) {
            publishBtn.click();
        }
    }
    
    // Ctrl+P 预览文章
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        previewArticle();
    }
});

console.log('Quill.js 文章编辑器 JavaScript 加载完成（编辑模式）');
</script>

<?php include '../templates/admin_footer.php'; ?>