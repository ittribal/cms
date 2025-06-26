<?php
// ==================== admin/content/article_add.php - 添加文章 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('content.create');

// 获取分类列表
$categories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

$errors = [];

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
        $existing = $db->fetchOne("SELECT id FROM articles WHERE slug = ?", [$slug]);
        if ($existing) {
            $slug .= '-' . time();
        }
        
        // 处理特色图片上传
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $upload_result = handle_file_upload($_FILES['featured_image'], 'uploads/articles/', $allowed_types);
                $featured_image = $upload_result['filepath'];
                
                // 保存媒体文件记录
                $db->insert('media_files', [
                    'filename' => $upload_result['filename'],
                    'original_name' => $upload_result['original_name'],
                    'file_path' => $upload_result['filepath'],
                    'file_size' => $upload_result['size'],
                    'mime_type' => $upload_result['type'],
                    'uploaded_by' => $_SESSION['user_id']
                ]);
            } catch (Exception $e) {
                $errors[] = '图片上传失败: ' . $e->getMessage();
            }
        }
        
        // 自动生成摘要
        if (empty($excerpt)) {
            $excerpt = mb_substr(strip_tags($content), 0, 200) . '...';
        }
        
        if (empty($errors)) {
            try {
                $article_data = [
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'excerpt' => $excerpt,
                    'category_id' => $category_id ?: null,
                    'author_id' => $_SESSION['user_id'],
                    'status' => $status,
                    'featured_image' => $featured_image,
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description
                ];
                
                // 如果状态为已发布，设置发布时间
                if ($status === 'published') {
                    $article_data['published_at'] = date('Y-m-d H:i:s');
                }
                
                $article_id = $db->insert('articles', $article_data);
                
                // 记录日志
                $auth->logAction($_SESSION['user_id'], 'article_create', 'articles', $article_id);
                
                set_flash_message('文章创建成功', 'success');
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
    <title>写文章 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/35.4.0/classic/ckeditor.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">写文章</h1>
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
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" class="article-form">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="form-layout">
                    <div class="main-content">
                        <div class="content-card">
                            <div class="form-group">
                                <label for="title">文章标题 *</label>
                                <input type="text" id="title" name="title" required 
                                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                       class="form-control form-control-lg"
                                       placeholder="输入吸引人的标题...">
                            </div>
                            
                            <div class="form-group">
                                <label for="content">文章内容 *</label>
                                <textarea id="content" name="content" rows="20" 
                                          class="form-control"
                                          placeholder="开始写作..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="excerpt">文章摘要</label>
                                <textarea id="excerpt" name="excerpt" rows="3" 
                                          class="form-control" 
                                          placeholder="留空将自动从内容中提取..."><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <!-- SEO设置 -->
                        <div class="content-card">
                            <h3 class="card-title">SEO 优化</h3>
                            <div class="form-group">
                                <label for="meta_title">SEO标题</label>
                                <input type="text" id="meta_title" name="meta_title" 
                                       value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>"
                                       class="form-control"
                                       placeholder="搜索引擎显示的标题">
                                <small class="form-help">建议长度：50-60个字符</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">SEO描述</label>
                                <textarea id="meta_description" name="meta_description" rows="3" 
                                          class="form-control"
                                          placeholder="搜索引擎显示的描述"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
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
                                    <option value="draft" <?= ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>草稿</option>
                                    <option value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'selected' : '' ?>>立即发布</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">分类</label>
                                <select id="category_id" name="category_id" class="form-control">
                                    <option value="">请选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <span class="icon">📝</span> 发布文章
                                </button>
                                <a href="articles.php" class="btn btn-outline btn-block">取消</a>
                            </div>
                        </div>
                        
                        <!-- 特色图片 -->
                        <div class="content-card">
                            <h3 class="card-title">特色图片</h3>
                            <div class="form-group">
                                <input type="file" name="featured_image" accept="image/*" 
                                       class="form-control" id="featured_image">
                                <small class="form-help">支持 JPG, PNG, GIF, WebP 格式</small>
                            </div>
                            
                            <div id="image_preview" style="display: none;">
                                <img id="preview_img" style="max-width: 100%; height: auto; border-radius: 6px;">
                            </div>
                        </div>
                        
                        <!-- 写作提示 -->
                        <div class="content-card">
                            <h3 class="card-title">📝 写作提示</h3>
                            <ul class="writing-tips">
                                <li>使用清晰的标题和副标题</li>
                                <li>保持段落简短易读</li>
                                <li>添加相关的代码示例</li>
                                <li>使用列表和表格组织信息</li>
                                <li>检查拼写和语法错误</li>
                            </ul>
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
                    'link', 'blockQuote', 'insertTable', 'code', 'codeBlock', '|',
                    'undo', 'redo'
                ],
                heading: {
                    options: [
                        { model: 'paragraph', title: '段落', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: '标题 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: '标题 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: '标题 3', class: 'ck-heading_heading3' }
                    ]
                },
                codeBlock: {
                    languages: [
                        { language: 'html', label: 'HTML' },
                        { language: 'css', label: 'CSS' },
                        { language: 'javascript', label: 'JavaScript' },
                        { language: 'php', label: 'PHP' },
                        { language: 'sql', label: 'SQL' },
                        { language: 'bash', label: 'Bash' }
                    ]
                }
            })
            .catch(error => {
                console.error(error);
            });
        
        // 图片预览功能
        document.getElementById('featured_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview_img').src = e.target.result;
                    document.getElementById('image_preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('image_preview').style.display = 'none';
            }
        });
        
        // 自动保存草稿功能
        let autoSaveTimer;
        let editor;
        
        ClassicEditor.create(document.querySelector('#content')).then(editorInstance => {
            editor = editorInstance;
            
            editor.model.document.on('change:data', () => {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(autoSave, 30000); // 30秒后自动保存
            });
        });
        
        function autoSave() {
            const title = document.getElementById('title').value;
            const content = editor.getData();
            
            if (title && content) {
                // 这里可以发送AJAX请求保存草稿
                console.log('Auto saving draft...');
            }
        }
        
        // 字数统计
        function updateWordCount() {
            const content = editor.getData();
            const wordCount = content.replace(/<[^>]*>/g, '').length;
            
            let countElement = document.getElementById('word-count');
            if (!countElement) {
                countElement = document.createElement('div');
                countElement.id = 'word-count';
                countElement.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #667eea; color: white; padding: 8px 12px; border-radius: 6px; font-size: 12px; z-index: 1000;';
                document.body.appendChild(countElement);
            }
            
            countElement.textContent = `字数: ${wordCount}`;
        }
        
        // 监听编辑器内容变化
        if (editor) {
            editor.model.document.on('change:data', updateWordCount);
        }
    </script>
    
    <style>
        .writing-tips {
            list-style: none;
            padding: 0;
        }
        
        .writing-tips li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .writing-tips li:before {
            content: '💡';
            margin-right: 8px;
        }
        
        .writing-tips li:last-child {
            border-bottom: none;
        }
        
        .ck-editor__editable {
            min-height: 400px;
        }
        
        .form-control-lg {
            font-size: 1.25rem;
            font-weight: 600;
        }
    </style>
</body>
</html>