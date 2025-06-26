<?php
// admin/article_form.php - 文章添加/编辑表单
// 获取文章数据（编辑模式）
$article = null;
$tags = '';
if ($action === 'edit' && $articleId) {
    $article = $db->fetchOne("SELECT * FROM articles WHERE id = ?", [$articleId]);
    if (!$article) {
        header('Location: articles.php?error=' . urlencode('文章不存在'));
        exit;
    }
    
    // 获取文章标签
    $articleTags = $db->fetchAll("
        SELECT t.name 
        FROM tags t 
        INNER JOIN article_tags at ON t.id = at.tag_id 
        WHERE at.article_id = ?", [$articleId]);
    $tags = implode(', ', array_column($articleTags, 'name'));
}

// 获取分类列表
$categories = $db->fetchAll("SELECT id, name, parent_id FROM categories WHERE status = 'active' ORDER BY name");

// 构建分类树
function buildCategoryTree($categories, $parentId = null, $level = 0) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $category['level'] = $level;
            $tree[] = $category;
            $tree = array_merge($tree, buildCategoryTree($categories, $category['id'], $level + 1));
        }
    }
    return $tree;
}

$categoryTree = buildCategoryTree($categories);

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1>
                    <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i>
                    <?php echo $action === 'add' ? '新建文章' : '编辑文章'; ?>
                </h1>
                <p><?php echo $action === 'add' ? '创建新的文章内容' : '修改文章内容和设置'; ?></p>
            </div>
            <div class="header-actions">
                <a href="articles.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
                <?php if ($action === 'edit' && $article): ?>
                    <a href="../public/article.php?slug=<?php echo $article['slug']; ?>" 
                       target="_blank" class="btn btn-info">
                        <i class="fas fa-external-link-alt"></i> 预览文章
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" id="articleForm" enctype="multipart/form-data" class="article-form">
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <?php if ($action === 'edit' && $article): ?>
                <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
            <?php endif; ?>
            
            <div class="form-layout">
                <!-- 主要内容区域 -->
                <div class="main-form">
                    <!-- 基本信息 -->
                    <div class="form-section">
                        <h3><i class="fas fa-file-alt"></i> 基本信息</h3>
                        
                        <div class="form-group">
                            <label for="title" class="required">文章标题</label>
                            <input type="text" id="title" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($article['title'] ?? ''); ?>" 
                                   placeholder="请输入文章标题" required>
                            <small class="form-text">建议标题长度在10-60个字符之间</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">URL别名</label>
                            <input type="text" id="slug" name="slug" class="form-control" 
                                   value="<?php echo htmlspecialchars($article['slug'] ?? ''); ?>" 
                                   placeholder="留空自动生成">
                            <small class="form-text">用于生成文章链接，留空将根据标题自动生成</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="excerpt">文章摘要</label>
                            <textarea id="excerpt" name="excerpt" class="form-control" rows="3" 
                                      placeholder="文章摘要，留空将自动从内容中提取"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
                            <small class="form-text">建议摘要长度在100-200个字符</small>
                        </div>
                    </div>

                    <!-- 文章内容 -->
                    <div class="form-section">
                        <h3><i class="fas fa-edit"></i> 文章内容</h3>
                        
                        <div class="editor-toolbar">
                            <div class="toolbar-group">
                                <button type="button" class="btn-toolbar" onclick="insertText('**', '**')" title="粗体">
                                    <i class="fas fa-bold"></i>
                                </button>
                                <button type="button" class="btn-toolbar" onclick="insertText('*', '*')" title="斜体">
                                    <i class="fas fa-italic"></i>
                                </button>
                                <button type="button" class="btn-toolbar" onclick="insertText('`', '`')" title="代码">
                                    <i class="fas fa-code"></i>
                                </button>
                            </div>
                            
                            <div class="toolbar-group">
                                <button type="button" class="btn-toolbar" onclick="insertText('# ', '')" title="标题1">
                                    H1
                                </button>
                                <button type="button" class="btn-toolbar" onclick="insertText('## ', '')" title="标题2">
                                    H2
                                </button>
                                <button type="button" class="btn-toolbar" onclick="insertText('### ', '')" title="标题3">
                                    H3
                                </button>
                            </div>
                            
                            <div class="toolbar-group">
                                <button type="button" class="btn-toolbar" onclick="insertText('- ', '')" title="无序列表">
                                    <i class="fas fa-list-ul"></i>
                                </button>
                                <button type="button" class="btn-toolbar" onclick="insertText('1. ', '')" title="有序列表">
                                    <i class="fas fa-list-ol"></i>
                                </button>
                                <button type="button" class="btn-toolbar" onclick="insertText('> ', '')" title="引用">
                                    <i class="fas fa-quote-right"></i>
                                </button>
                            </div>
                            
                            <div class="toolbar-group">
                                <button type="button" class="btn-toolbar" onclick="insertLink()" title="插入链接">
                                    <i class="fas fa-link"></i>
                                </button>
                                <button type="button" class="btn-toolbar" onclick="insertImage()" title="插入图片">
                                    <i class="fas fa-image"></i>
                                </button>
                                <button type="button" class="btn-toolbar" onclick="showMediaLibrary()" title="媒体库">
                                    <i class="fas fa-folder-open"></i>
                                </button>
                            </div>
                            
                            <div class="toolbar-group">
                                <button type="button" class="btn-toolbar" onclick="togglePreview()" title="预览">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn-toolbar" onclick="toggleFullscreen()" title="全屏">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="editor-container">
                            <div class="editor-wrapper">
                                <textarea id="content" name="content" class="form-control content-editor" 
                                          placeholder="请输入文章内容，支持Markdown格式" required><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
                                
                                <div id="contentPreview" class="content-preview" style="display: none;">
                                    <!-- 预览内容 -->
                                </div>
                            </div>
                            
                            <div class="editor-status">
                                <span class="word-count">字数: <span id="wordCount">0</span></span>
                                <span class="save-status" id="saveStatus"></span>
                            </div>
                        </div>
                    </div>

                    <!-- SEO设置 -->
                    <div class="form-section">
                        <h3>
                            <i class="fas fa-search"></i> SEO设置
                            <button type="button" class="btn-toggle" onclick="toggleSection('seoSection')">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </h3>
                        
                        <div id="seoSection" class="section-content">
                            <div class="form-group">
                                <label for="meta_title">SEO标题</label>
                                <input type="text" id="meta_title" name="meta_title" class="form-control" 
                                       value="<?php echo htmlspecialchars($article['meta_title'] ?? ''); ?>" 
                                       placeholder="留空使用文章标题">
                                <div class="seo-preview">
                                    <div class="preview-title" id="previewTitle">
                                        <?php echo htmlspecialchars($article['meta_title'] ?? $article['title'] ?? '文章标题'); ?>
                                    </div>
                                    <div class="preview-url" id="previewUrl">
                                        <?php echo SITE_URL; ?>/article/<?php echo $article['slug'] ?? 'article-slug'; ?>
                                    </div>
                                    <div class="preview-description" id="previewDescription">
                                        <?php echo htmlspecialchars($article['meta_description'] ?? $article['excerpt'] ?? '文章描述...'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">SEO描述</label>
                                <textarea id="meta_description" name="meta_description" class="form-control" rows="3" 
                                          placeholder="留空使用文章摘要"><?php echo htmlspecialchars($article['meta_description'] ?? ''); ?></textarea>
                                <small class="form-text">建议长度120-160个字符</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_keywords">关键词</label>
                                <input type="text" id="meta_keywords" name="meta_keywords" class="form-control" 
                                       value="<?php echo htmlspecialchars($article['meta_keywords'] ?? ''); ?>" 
                                       placeholder="关键词1, 关键词2, 关键词3">
                                <small class="form-text">多个关键词用逗号分隔，建议3-5个</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 侧边栏 -->
                <div class="sidebar-form">
                    <!-- 发布设置 -->
                    <div class="form-section">
                        <h3><i class="fas fa-cog"></i> 发布设置</h3>
                        
                        <div class="form-group">
                            <label for="status">文章状态</label>
                            <select id="status" name="status" class="form-control">
                                <option value="draft" <?php echo ($article['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="published" <?php echo ($article['status'] ?? '') === 'published' ? 'selected' : ''; ?>>已发布</option>
                                <option value="pending" <?php echo ($article['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>待审核</option>
                                <option value="private" <?php echo ($article['status'] ?? '') === 'private' ? 'selected' : ''; ?>>私密</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">文章分类</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="">请选择分类</option>
                                <?php foreach ($categoryTree as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($article['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo str_repeat('—', $category['level']); ?>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tags">文章标签</label>
                            <input type="text" id="tags" name="tags" class="form-control" 
                                   value="<?php echo htmlspecialchars($tags); ?>" 
                                   placeholder="标签1, 标签2, 标签3">
                            <small class="form-text">多个标签用逗号分隔，不存在的标签会自动创建</small>
                        </div>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_comments" value="1" 
                                       <?php echo ($article['allow_comments'] ?? 1) ? 'checked' : ''; ?>>
                                允许评论
                            </label>
                            
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_featured" value="1" 
                                       <?php echo ($article['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                推荐文章
                            </label>
                        </div>
                    </div>

                    <!-- 特色图片 -->
                    <div class="form-section">
                        <h3><i class="fas fa-image"></i> 特色图片</h3>
                        
                        <div class="featured-image-container">
                            <div class="image-preview" id="imagePreview">
                                <?php if (!empty($article['featured_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="特色图片">
                                    <div class="image-actions">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeFeaturedImage()">
                                            <i class="fas fa-trash"></i> 移除
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image"></i>
                                        <p>暂无特色图片</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <input type="hidden" id="featured_image" name="featured_image" 
                                   value="<?php echo htmlspecialchars($article['featured_image'] ?? ''); ?>">
                            
                            <div class="image-upload-buttons">
                                <button type="button" class="btn btn-primary" onclick="selectFeaturedImage()">
                                    <i class="fas fa-upload"></i> 选择图片
                                </button>
                                <button type="button" class="btn btn-info" onclick="showMediaLibrary()">
                                    <i class="fas fa-folder-open"></i> 媒体库
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 发布时间 -->
                    <div class="form-section">
                        <h3><i class="fas fa-clock"></i> 发布时间</h3>
                        
                        <div class="form-group">
                            <label>
                                <input type="radio" name="publish_time_type" value="now" checked>
                                立即发布
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="radio" name="publish_time_type" value="scheduled">
                                定时发布
                            </label>
                            <input type="datetime-local" name="scheduled_time" class="form-control" 
                                   style="margin-top: 0.5rem;" disabled>
                        </div>
                        
                        <?php if ($action === 'edit' && $article): ?>
                            <div class="publish-info">
                                <small class="text-muted">
                                    创建时间: <?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?>
                                    <?php if ($article['updated_at'] !== $article['created_at']): ?>
                                        <br>更新时间: <?php echo date('Y-m-d H:i', strtotime($article['updated_at'])); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'add' ? '发布文章' : '更新文章'; ?>
                        </button>
                        
                        <button type="button" class="btn btn-secondary" onclick="saveDraft()">
                            <i class="fas fa-file"></i> 保存草稿
                        </button>
                        
                        <button type="button" class="btn btn-info" onclick="previewArticle()">
                            <i class="fas fa-eye"></i> 预览
                        </button>
                        
                        <?php if ($action === 'edit' && $article): ?>
                            <button type="button" class="btn btn-danger" onclick="deleteArticle(<?php echo $article['id']; ?>)">
                                <i class="fas fa-trash"></i> 删除
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<!-- 媒体库模态框 -->
<div id="mediaLibraryModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3><i class="fas fa-folder-open"></i> 媒体库</h3>
            <button type="button" class="close" onclick="closeModal('mediaLibraryModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="media-library">
                <div class="media-toolbar">
                    <div class="upload-area">
                        <input type="file" id="mediaUpload" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('mediaUpload').click()">
                            <i class="fas fa-upload"></i> 上传文件
                        </button>
                    </div>
                    
                    <div class="media-filters">
                        <select id="mediaType" class="form-control">
                            <option value="">所有类型</option>
                            <option value="image">图片</option>
                            <option value="video">视频</option>
                            <option value="audio">音频</option>
                            <option value="document">文档</option>
                        </select>
                        
                        <input type="text" id="mediaSearch" class="form-control" placeholder="搜索文件...">
                    </div>
                </div>
                
                <div class="media-grid" id="mediaGrid">
                    <!-- 动态加载媒体文件 -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 文章表单样式 */
.article-form {
    max-width: none;
}

.form-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    align-items: start;
}

.main-form {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sidebar-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-section {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
}

.sidebar-form .form-section {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-bottom: none;
}

.form-section h3 {
    margin: 0 0 1.5rem 0;
    color: #2c3e50;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-toggle {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    margin-left: auto;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.btn-toggle:hover {
    background: #f8f9fa;
    color: #495057;
}

.btn-toggle.collapsed i {
    transform: rotate(-90deg);
}

.section-content {
    max-height: 1000px;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.section-content.collapsed {
    max-height: 0;
}

/* 编辑器样式 */
.editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
}

.toolbar-group {
    display: flex;
    gap: 0.25rem;
    border-right: 1px solid #dee2e6;
    padding-right: 0.5rem;
}

.toolbar-group:last-child {
    border-right: none;
    padding-right: 0;
}

.btn-toolbar {
    background: white;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-toolbar:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.btn-toolbar:active,
.btn-toolbar.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.editor-container {
    border: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    overflow: hidden;
}

.editor-wrapper {
    position: relative;
}

.content-editor {
    border: none;
    border-radius: 0;
    min-height: 400px;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.6;
    resize: vertical;
}

.content-preview {
    position: absolute;
    top: 0;
    left: 50%;
    right: 0;
    bottom: 0;
    background: white;
    border-left: 1px solid #dee2e6;
    padding: 1rem;
    overflow-y: auto;
}

.editor-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    font-size: 0.85rem;
    color: #6c757d;
}

.save-status {
    color: #28a745;
    font-weight: 500;
}

/* SEO预览样式 */
.seo-preview {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.preview-title {
    color: #1a0dab;
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    cursor: pointer;
}

.preview-title:hover {
    text-decoration: underline;
}

.preview-url {
    color: #006621;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.preview-description {
    color: #545454;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* 特色图片样式 */
.featured-image-container {
    text-align: center;
}

.image-preview {
    position: relative;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: border-color 0.2s ease;
}

.image-preview:hover {
    border-color: #3498db;
}

.image-preview img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
}

.image-placeholder {
    color: #6c757d;
    padding: 2rem;
}

.image-placeholder i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.image-actions {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: none;
}

.image-preview:hover .image-actions {
    display: block;
}

.image-upload-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

/* 复选框组样式 */
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
}

.checkbox-label input[type="checkbox"] {
    margin: 0;
}

/* 表单操作按钮 */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.form-actions .btn {
    width: 100%;
    justify-content: center;
}

.publish-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

/* 媒体库样式 */
.modal-large {
    max-width: 1000px;
    width: 90%;
}

.media-library {
    height: 600px;
    display: flex;
    flex-direction: column;
}

.media-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
}

.media-filters {
    display: flex;
    gap: 1rem;
}

.media-filters select,
.media-filters input {
    width: 150px;
}

.media-grid {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}

.media-item {
    position: relative;
    aspect-ratio: 1;
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
}

.media-item:hover {
    border-color: #3498db;
    transform: scale(1.02);
}

.media-item.selected {
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.3);
}

.media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-item-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    color: white;
    padding: 0.5rem;
    font-size: 0.8rem;
    text-align: center;
}

/* 响应式设计 */
@media (max-width: 1200px) {
    .form-layout {
        grid-template-columns: 1fr 280px;
        gap: 1.5rem;
    }
}

@media (max-width: 968px) {
    .form-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .sidebar-form {
        order: -1;
    }
    
    .sidebar-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
}

@media (max-width: 768px) {
    .editor-toolbar {
        gap: 0.25rem;
    }
    
    .toolbar-group {
        gap: 0.125rem;
        padding-right: 0.25rem;
    }
    
    .btn-toolbar {
        width: 32px;
        height: 32px;
        padding: 0.25rem;
    }
    
    .content-editor {
        min-height: 300px;
        font-size: 16px; /* 防止iOS缩放 */
    }
    
    .sidebar-form {
        grid-template-columns: 1fr;
    }
}

/* 全屏编辑器样式 */
.editor-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    background: white;
    display: flex;
    flex-direction: column;
}

.editor-fullscreen .editor-container {
    flex: 1;
    border-radius: 0;
    border: none;
}

.editor-fullscreen .content-editor {
    min-height: auto;
    height: 100%;
    border-radius: 0;
}

.fullscreen-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.fullscreen-title {
    font-size: 1.1rem;
    font-weight: 500;
    color: #2c3e50;
}

.fullscreen-actions {
    display: flex;
    gap: 0.5rem;
}

/* 拖拽上传样式 */
.drag-over {
    border-color: #3498db !important;
    background-color: rgba(52, 152, 219, 0.1) !important;
}

.drag-over::after {
    content: '拖拽文件到此处上传';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.2rem;
    color: #3498db;
    font-weight: 500;
    pointer-events: none;
}

/* 标签输入提示 */
.tag-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 150px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.tag-suggestion {
    padding: 0.5rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
}

.tag-suggestion:hover,
.tag-suggestion.active {
    background: #f8f9fa;
}

.tag-suggestion:last-child {
    border-bottom: none;
}

/* 自动保存指示器 */
.autosave-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.85rem;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 1000;
}

.autosave-indicator.show {
    opacity: 1;
}

.autosave-indicator.error {
    background: #dc3545;
}
</style>

<script>
// 文章表单JavaScript功能

let isFullscreen = false;
let isPreviewMode = false;
let autosaveInterval;
let tagSuggestions = [];

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeEditor();
    initializeFormValidation();
    initializeAutosave();
    initializeTagInput();
    initializeSEOPreview();
    initializeMediaUpload();
    initializeShortcuts();
    
    // 加载标签建议
    loadTagSuggestions();
});

// 初始化编辑器
function initializeEditor() {
    const contentEditor = document.getElementById('content');
    const wordCountElement = document.getElementById('wordCount');
    
    if (contentEditor) {
        // 更新字数统计
        function updateWordCount() {
            const text = contentEditor.value;
            const wordCount = text.length;
            wordCountElement.textContent = wordCount;
        }
        
        contentEditor.addEventListener('input', updateWordCount);
        updateWordCount(); // 初始化字数
        
        // 拖拽上传支持
        contentEditor.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        contentEditor.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });
        
        contentEditor.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                uploadFiles(files);
            }
        });
    }
}

// 编辑器工具栏功能
function insertText(before, after = '') {
    const editor = document.getElementById('content');
    const start = editor.selectionStart;
    const end = editor.selectionEnd;
    const selectedText = editor.value.substring(start, end);
    
    const newText = before + selectedText + after;
    
    editor.value = editor.value.substring(0, start) + newText + editor.value.substring(end);
    
    // 重新设置光标位置
    const newCursorPos = start + before.length + selectedText.length + after.length;
    editor.setSelectionRange(newCursorPos, newCursorPos);
    editor.focus();
    
    // 触发输入事件以更新字数统计
    editor.dispatchEvent(new Event('input'));
}

function insertLink() {
    const url = prompt('请输入链接地址:');
    if (url) {
        const text = prompt('请输入链接文字:', url);
        insertText(`[${text || url}](${url})`);
    }
}

function insertImage() {
    const url = prompt('请输入图片地址:');
    if (url) {
        const alt = prompt('请输入图片描述:', '图片');
        insertText(`![${alt}](${url})`);
    }
}

// 切换预览模式
function togglePreview() {
    const editor = document.getElementById('content');
    const preview = document.getElementById('contentPreview');
    const button = event.target.closest('.btn-toolbar');
    
    if (!isPreviewMode) {
        // 显示预览
        preview.innerHTML = marked ? marked(editor.value) : editor.value.replace(/\n/g, '<br>');
        preview.style.display = 'block';
        editor.style.width = '50%';
        button.classList.add('active');
        isPreviewMode = true;
    } else {
        // 隐藏预览
        preview.style.display = 'none';
        editor.style.width = '100%';
        button.classList.remove('active');
        isPreviewMode = false;
    }
}

// 切换全屏模式
function toggleFullscreen() {
    const editorContainer = document.querySelector('.editor-container');
    const button = event.target.closest('.btn-toolbar');
    
    if (!isFullscreen) {
        // 进入全屏
        editorContainer.classList.add('editor-fullscreen');
        
        // 添加全屏头部
        const header = document.createElement('div');
        header.className = 'fullscreen-header';
        header.innerHTML = `
            <div class="fullscreen-title">文章编辑 - 全屏模式</div>
            <div class="fullscreen-actions">
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleFullscreen()">
                    <i class="fas fa-compress"></i> 退出全屏
                </button>
            </div>
        `;
        
        editorContainer.insertBefore(header, editorContainer.firstChild);
        button.classList.add('active');
        isFullscreen = true;
        
        // ESC键退出全屏
        document.addEventListener('keydown', escapeFullscreen);
    } else {
        // 退出全屏
        exitFullscreen();
    }
}

function exitFullscreen() {
    const editorContainer = document.querySelector('.editor-container');
    const header = editorContainer.querySelector('.fullscreen-header');
    const button = document.querySelector('.btn-toolbar.active');
    
    if (header) {
        header.remove();
    }
    
    editorContainer.classList.remove('editor-fullscreen');
    if (button) {
        button.classList.remove('active');
    }
    isFullscreen = false;
    
    document.removeEventListener('keydown', escapeFullscreen);
}

function escapeFullscreen(e) {
    if (e.key === 'Escape' && isFullscreen) {
        exitFullscreen();
    }
}

// 显示媒体库
function showMediaLibrary() {
    const modal = document.getElementById('mediaLibraryModal');
    modal.style.display = 'block';
    loadMediaLibrary();
}

// 加载媒体库
function loadMediaLibrary() {
    const mediaGrid = document.getElementById('mediaGrid');
    
    // 模拟媒体文件数据
    const mediaFiles = [
        { id: 1, name: 'image1.jpg', type: 'image', url: '/uploads/image1.jpg', size: '2.5MB' },
        { id: 2, name: 'image2.png', type: 'image', url: '/uploads/image2.png', size: '1.8MB' },
        { id: 3, name: 'document.pdf', type: 'document', url: '/uploads/document.pdf', size: '850KB' }
    ];
    
    mediaGrid.innerHTML = '';
    
    mediaFiles.forEach(file => {
        const mediaItem = document.createElement('div');
        mediaItem.className = 'media-item';
        mediaItem.dataset.id = file.id;
        mediaItem.onclick = () => selectMediaItem(file);
        
        if (file.type === 'image') {
            mediaItem.innerHTML = `
                <img src="${file.url}" alt="${file.name}">
                <div class="media-item-info">
                    <div>${file.name}</div>
                    <div>${file.size}</div>
                </div>
            `;
        } else {
            mediaItem.innerHTML = `
                <div class="media-file-icon">
                    <i class="fas fa-file-${file.type === 'document' ? 'pdf' : 'alt'}"></i>
                </div>
                <div class="media-item-info">
                    <div>${file.name}</div>
                    <div>${file.size}</div>
                </div>
            `;
        }
        
        mediaGrid.appendChild(mediaItem);
    });
}

// 选择媒体文件
function selectMediaItem(file) {
    if (file.type === 'image') {
        insertText(`![${file.name}](${file.url})`);
    } else {
        insertText(`[${file.name}](${file.url})`);
    }
    
    closeModal('mediaLibraryModal');
}

// 选择特色图片
function selectFeaturedImage() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    
    input.onchange = function() {
        const file = this.files[0];
        if (file) {
            uploadFeaturedImage(file);
        }
    };
    
    input.click();
}

// 上传特色图片
function uploadFeaturedImage(file) {
    const formData = new FormData();
    formData.append('image', file);
    
    showLoading('正在上传图片...');
    
    fetch('upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            updateFeaturedImagePreview(data.url);
            document.getElementById('featured_image').value = data.url;
            showToast('图片上传成功', 'success');
        } else {
            showToast('图片上传失败: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('图片上传失败', 'error');
    });
}

// 更新特色图片预览
function updateFeaturedImagePreview(url) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = `
        <img src="${url}" alt="特色图片">
        <div class="image-actions">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeFeaturedImage()">
                <i class="fas fa-trash"></i> 移除
            </button>
        </div>
    `;
}

// 移除特色图片
function removeFeaturedImage() {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = `
        <div class="image-placeholder">
            <i class="fas fa-image"></i>
            <p>暂无特色图片</p>
        </div>
    `;
    
    document.getElementById('featured_image').value = '';
}

// 切换表单节
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const button = event.target.closest('.btn-toggle');
    
    if (section.classList.contains('collapsed')) {
        section.classList.remove('collapsed');
        button.classList.remove('collapsed');
    } else {
        section.classList.add('collapsed');
        button.classList.add('collapsed');
    }
}

// 保存草稿
function saveDraft() {
    const statusSelect = document.getElementById('status');
    const originalStatus = statusSelect.value;
    
    statusSelect.value = 'draft';
    
    const form = document.getElementById('articleForm');
    const formData = new FormData(form);
    
    showLoading('正在保存草稿...');
    
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        hideLoading();
        showToast('草稿保存成功', 'success');
    })
    .catch(error => {
        hideLoading();
        showToast('草稿保存失败', 'error');
        statusSelect.value = originalStatus;
    });
}

// 预览文章
function previewArticle() {
    const form = document.getElementById('articleForm');
    const formData = new FormData(form);
    
    // 创建预览表单
    const previewForm = document.createElement('form');
    previewForm.method = 'POST';
    previewForm.action = 'preview_article.php';
    previewForm.target = '_blank';
    previewForm.style.display = 'none';
    
    // 添加表单数据
    for (const [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.name = key;
        input.value = value;
        previewForm.appendChild(input);
    }
    
    document.body.appendChild(previewForm);
    previewForm.submit();
    previewForm.remove();
}

// 初始化表单验证
function initializeFormValidation() {
    const form = document.getElementById('articleForm');
    
    form.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const content = document.getElementById('content').value.trim();
        
        if (!title) {
            e.preventDefault();
            showToast('请输入文章标题', 'error');
            document.getElementById('title').focus();
            return;
        }
        
        if (!content) {
            e.preventDefault();
            showToast('请输入文章内容', 'error');
            document.getElementById('content').focus();
            return;
        }
        
        if (title.length < 2) {
            e.preventDefault();
            showToast('文章标题至少需要2个字符', 'error');
            document.getElementById('title').focus();
            return;
        }
        
        if (content.length < 10) {
            e.preventDefault();
            showToast('文章内容至少需要10个字符', 'error');
            document.getElementById('content').focus();
            return;
        }
        
        // 显示提交loading
        showLoading('正在保存文章...');
    });
}

// 初始化自动保存
function initializeAutosave() {
    autosaveInterval = setInterval(() => {
        autoSave();
    }, 30000); // 每30秒自动保存
    
    // 页面离开时保存
    window.addEventListener('beforeunload', function(e) {
        const form = document.getElementById('articleForm');
        const formData = new FormData(form);
        
        // 检查是否有未保存的更改
        if (hasUnsavedChanges()) {
            e.preventDefault();
            e.returnValue = '您有未保存的更改，确定要离开吗？';
            return e.returnValue;
        }
    });
}

// 自动保存
function autoSave() {
    const form = document.getElementById('articleForm');
    const formData = new FormData(form);
    
    // 添加自动保存标识
    formData.append('autosave', '1');
    
    fetch('autosave_article.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showAutosaveIndicator(data.success);
    })
    .catch(error => {
        showAutosaveIndicator(false);
    });
}

// 显示自动保存指示器
function showAutosaveIndicator(success) {
    let indicator = document.querySelector('.autosave-indicator');
    
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'autosave-indicator';
        document.body.appendChild(indicator);
    }
    
    indicator.textContent = success ? '已自动保存' : '自动保存失败';
    indicator.className = 'autosave-indicator' + (success ? '' : ' error');
    indicator.classList.add('show');
    
    setTimeout(() => {
        indicator.classList.remove('show');
    }, 3000);
}

// 检查是否有未保存的更改
function hasUnsavedChanges() {
    // 简单的检查逻辑，实际项目中可以更复杂
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    
    return title.length > 0 || content.length > 0;
}

// 初始化标签输入
function initializeTagInput() {
    const tagInput = document.getElementById('tags');
    
    if (tagInput) {
        // 创建建议容器
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'tag-suggestions';
        tagInput.parentNode.style.position = 'relative';
        tagInput.parentNode.appendChild(suggestionsContainer);
        
        tagInput.addEventListener('input', function() {
            const value = this.value;
            const lastComma = value.lastIndexOf(',');
            const currentTag = value.substring(lastComma + 1).trim();
            
            if (currentTag.length >= 1) {
                showTagSuggestions(currentTag, suggestionsContainer);
            } else {
                hidTagSuggestions(suggestionsContainer);
            }
        });
        
        tagInput.addEventListener('blur', function() {
            // 延迟隐藏，以便点击建议项
            setTimeout(() => {
                hidTagSuggestions(suggestionsContainer);
            }, 200);
        });
    }
}

// 加载标签建议
function loadTagSuggestions() {
    fetch('get_tags.php')
        .then(response => response.json())
        .then(data => {
            tagSuggestions = data.tags || [];
        })
        .catch(error => {
            console.error('加载标签建议失败:', error);
        });
}

// 显示标签建议
function showTagSuggestions(query, container) {
    const matches = tagSuggestions.filter(tag => 
        tag.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 5);
    
    if (matches.length > 0) {
        container.innerHTML = matches.map(tag => 
            `<div class="tag-suggestion" onclick="selectTagSuggestion('${tag}')">${tag}</div>`
        ).join('');
        container.style.display = 'block';
    } else {
        hidTagSuggestions(container);
    }
}

// 隐藏标签建议
function hidTagSuggestions(container) {
    container.style.display = 'none';
}

// 选择标签建议
function selectTagSuggestion(tag) {
    const tagInput = document.getElementById('tags');
    const value = tagInput.value;
    const lastComma = value.lastIndexOf(',');
    
    if (lastComma >= 0) {
        tagInput.value = value.substring(0, lastComma + 1) + ' ' + tag + ', ';
    } else {
        tagInput.value = tag + ', ';
    }
    
    tagInput.focus();
    hidTagSuggestions(document.querySelector('.tag-suggestions'));
}

// 初始化SEO预览
function initializeSEOPreview() {
    const titleInput = document.getElementById('title');
    const metaTitleInput = document.getElementById('meta_title');
    const metaDescInput = document.getElementById('meta_description');
    const excerptInput = document.getElementById('excerpt');
    const slugInput = document.getElementById('slug');
    
    function updateSEOPreview() {
        const title = metaTitleInput.value || titleInput.value || '文章标题';
        const description = metaDescInput.value || excerptInput.value || '文章描述...';
        const slug = slugInput.value || 'article-slug';
        
        document.getElementById('previewTitle').textContent = title;
        document.getElementById('previewDescription').textContent = description;
        document.getElementById('previewUrl').textContent = `${window.location.origin}/article/${slug}`;
    }
    
    [titleInput, metaTitleInput, metaDescInput, excerptInput, slugInput].forEach(input => {
        if (input) {
            input.addEventListener('input', updateSEOPreview);
        }
    });
    
    // 初始化预览
    updateSEOPreview();
}

// 初始化媒体上传
function initializeMediaUpload() {
    const mediaUpload = document.getElementById('mediaUpload');
    
    if (mediaUpload) {
        mediaUpload.addEventListener('change', function() {
            const files = this.files;
            if (files.length > 0) {
                uploadFiles(files);
            }
        });
    }
}

// 上传文件
function uploadFiles(files) {
    const formData = new FormData();
    
    Array.from(files).forEach((file, index) => {
        formData.append(`files[${index}]`, file);
    });
    
    showLoading(`正在上传 ${files.length} 个文件...`);
    
    fetch('upload_files.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showToast(`成功上传 ${data.uploaded.length} 个文件`, 'success');
            
            // 插入文件链接到编辑器
            data.uploaded.forEach(file => {
                if (file.type.startsWith('image/')) {
                    insertText(`![${file.name}](${file.url})\n`);
                } else {
                    insertText(`[${file.name}](${file.url})\n`);
                }
            });
            
            // 刷新媒体库
            loadMediaLibrary();
        } else {
            showToast('文件上传失败: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('文件上传失败', 'error');
    });
}

// 初始化快捷键
function initializeShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+S 保存
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveDraft();
        }
        
        // Ctrl+Enter 提交表单
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('articleForm').submit();
        }
        
        // F11 全屏编辑
        if (e.key === 'F11') {
            e.preventDefault();
            toggleFullscreen();
        }
    });
}

// 删除文章
function deleteArticle(id) {
    confirmAction('确定要删除这篇文章吗？删除后将移至回收站。', () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'articles.php';
        form.style.display = 'none';
        form.innerHTML = `
            <input name="action" value="delete">
            <input name="id" value="${id}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    });
}

// 关闭模态框
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// 页面卸载时清理
window.addEventListener('beforeunload', function() {
    if (autosaveInterval) {
        clearInterval(autosaveInterval);
    }
});
</script>

<?php include '../templates/admin_footer.php'; ?>