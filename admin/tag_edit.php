<?php
// admin/tag_edit.php - 编辑标签页面
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = '编辑标签';
$message = '';
$error = '';

// 获取标签ID
$tagId = $_GET['id'] ?? 0;
if (!$tagId) {
    header('Location: tags.php?error=' . urlencode('标签ID无效'));
    exit;
}

// 获取标签信息
$tag = $db->fetchOne("SELECT * FROM tags WHERE id = ?", [$tagId]);
if (!$tag) {
    header('Location: tags.php?error=' . urlencode('标签不存在'));
    exit;
}

// 处理表单提交
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'edit') {
    try {
        if (empty($_POST['name'])) {
            $error = '标签名称不能为空';
        } else {
            $slug = !empty($_POST['slug']) ? $_POST['slug'] : generate_slug($_POST['name']);
            
            // 检查名称和别名重复（排除当前标签）
            $existing = $db->fetchOne("SELECT id FROM tags WHERE (name = ? OR slug = ?) AND id != ?", 
                                    [$_POST['name'], $slug, $tagId]);
            
            if ($existing) {
                $error = '标签名称或别名已存在';
            } else {
                $tagData = [
                    'name' => $_POST['name'],
                    'slug' => $slug,
                    'description' => $_POST['description'] ?? '',
                    'color' => $_POST['color'] ?? '#3498db',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $sql = "UPDATE tags SET name = ?, slug = ?, description = ?, color = ?, updated_at = ? WHERE id = ?";
                $result = $db->execute($sql, array_merge(array_values($tagData), [$tagId]));
                
                if ($result) {
                    $auth->logAction('编辑标签', '标签ID: ' . $tagId);
                    
                    // 重新获取更新后的数据
                    $tag = $db->fetchOne("SELECT * FROM tags WHERE id = ?", [$tagId]);
                    $message = '标签更新成功';
                } else {
                    $error = '标签更新失败';
                }
            }
        }
    } catch (Exception $e) {
        $error = '操作失败：' . $e->getMessage();
    }
}

// 获取使用统计
$articleCount = $db->fetchOne("SELECT COUNT(*) as count FROM article_tags WHERE tag_id = ?", [$tagId]);
$articleCount = $articleCount ? $articleCount['count'] : 0;

$recentArticles = $db->fetchAll("
    SELECT a.id, a.title, a.slug, a.created_at 
    FROM articles a 
    INNER JOIN article_tags at ON a.id = at.article_id 
    WHERE at.tag_id = ? AND a.status = 'published' 
    ORDER BY a.created_at DESC 
    LIMIT 5
", [$tagId]);

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-edit"></i> 编辑标签</h1>
                <p>编辑标签信息和设置</p>
            </div>
            <div class="header-actions">
                <a href="tags.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
                <a href="../public/tag.php?slug=<?php echo $tag['slug']; ?>" target="_blank" class="btn btn-info">
                    <i class="fas fa-external-link-alt"></i> 预览标签
                </a>
            </div>
        </div>

        <!-- 标签表单 -->
        <div class="form-container">
            <div class="form-card">
                <form method="POST" id="tagForm">
                    <input type="hidden" name="action" value="edit">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> 基本信息</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name" class="required">标签名称</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($tag['name']); ?>" 
                                       placeholder="输入标签名称" required maxlength="50">
                                <small class="form-text">标签的显示名称，用于前台展示</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="slug">URL别名</label>
                                <input type="text" id="slug" name="slug" class="form-control" 
                                       value="<?php echo htmlspecialchars($tag['slug']); ?>" 
                                       placeholder="自动生成">
                                <small class="form-text">用于URL的别名</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">标签描述</label>
                            <textarea id="description" name="description" class="form-control" 
                                      rows="3" placeholder="输入标签描述（可选）"><?php echo htmlspecialchars($tag['description']); ?></textarea>
                            <small class="form-text">标签的详细描述，有助于SEO优化</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="color">标签颜色</label>
                            <div class="color-picker-group">
                                <input type="color" id="color" name="color" class="color-input" 
                                       value="<?php echo htmlspecialchars($tag['color']); ?>">
                                <div class="color-preview" id="colorPreview" 
                                     style="background-color: <?php echo htmlspecialchars($tag['color']); ?>"></div>
                                <span class="color-value"><?php echo htmlspecialchars($tag['color']); ?></span>
                                <div class="color-presets">
                                    <?php 
                                    $presetColors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#34495e', '#1abc9c', '#e67e22'];
                                    foreach ($presetColors as $color): 
                                    ?>
                                        <div class="color-preset" data-color="<?php echo $color; ?>" 
                                             style="background-color: <?php echo $color; ?>" 
                                             onclick="selectPresetColor('<?php echo $color; ?>')"></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <small class="form-text">用于标识标签的主题色</small>
                        </div>
                    </div>
                    
                    <!-- 使用统计 -->
                    <div class="form-section">
                        <h3><i class="fas fa-chart-bar"></i> 使用统计</h3>
                        
                        <div class="stats-info">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $articleCount; ?></div>
                                    <div class="stat-label">篇文章使用</div>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo date('Y-m-d', strtotime($tag['created_at'])); ?></div>
                                    <div class="stat-label">创建日期</div>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo date('Y-m-d', strtotime($tag['updated_at'])); ?></div>
                                    <div class="stat-label">更新日期</div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($recentArticles)): ?>
                            <div class="recent-articles">
                                <h4>使用此标签的文章</h4>
                                <div class="article-list">
                                    <?php foreach ($recentArticles as $article): ?>
                                        <div class="article-item">
                                            <div class="article-title">
                                                <a href="articles.php?action=edit&id=<?php echo $article['id']; ?>">
                                                    <?php echo htmlspecialchars($article['title']); ?>
                                                </a>
                                            </div>
                                            <div class="article-date">
                                                <?php echo date('Y-m-d', strtotime($article['created_at'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if ($articleCount > 5): ?>
                                    <div class="view-all">
                                        <a href="articles.php?tag=<?php echo $tag['id']; ?>" class="btn btn-sm btn-outline">
                                            查看全部 <?php echo $articleCount; ?> 篇文章
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 预览效果 -->
                    <div class="form-section">
                        <h3><i class="fas fa-eye"></i> 预览效果</h3>
                        
                        <div class="tag-preview">
                            <div class="preview-container">
                                <div class="tag-display" id="tagPreview">
                                    <span class="tag-item" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="preview-styles">
                                <h4>不同样式预览</h4>
                                <div class="style-variants">
                                    <div class="variant">
                                        <span class="variant-label">默认样式</span>
                                        <span class="tag-variant tag-default" id="previewDefault" 
                                              style="background-color: <?php echo htmlspecialchars($tag['color']); ?>">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="variant">
                                        <span class="variant-label">边框样式</span>
                                        <span class="tag-variant tag-outline" id="previewOutline"
                                              style="border-color: <?php echo htmlspecialchars($tag['color']); ?>; color: <?php echo htmlspecialchars($tag['color']); ?>">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="variant">
                                        <span class="variant-label">圆角样式</span>
                                        <span class="tag-variant tag-rounded" id="previewRounded"
                                              style="background-color: <?php echo htmlspecialchars($tag['color']); ?>">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 表单操作 -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 更新标签
                        </button>
                        
                        <button type="button" class="btn btn-info" onclick="previewTag()">
                            <i class="fas fa-eye"></i> 前台预览
                        </button>
                        
                        <button type="reset" class="btn btn-warning">
                            <i class="fas fa-undo"></i> 重置表单
                        </button>
                        
                        <a href="tags.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                        
                        <?php if ($articleCount == 0): ?>
                            <button type="button" class="btn btn-danger" onclick="deleteTag()">
                                <i class="fas fa-trash"></i> 删除标签
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<style>
/* 表单样式 */
.form-container {
    max-width: 900px;
    margin: 0 auto;
}

.form-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.form-section {
    padding: 2rem;
    border-bottom: 1px solid #f0f0f0;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #3498db;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #2c3e50;
}

.form-group label.required::after {
    content: ' *';
    color: #e74c3c;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.8rem;
    color: #6c757d;
}

/* 颜色选择器 */
.color-picker-group {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.color-input {
    width: 50px;
    height: 40px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.color-preview {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 2px solid #dee2e6;
    cursor: pointer;
}

.color-value {
    font-family: monospace;
    color: #495057;
    font-weight: 500;
    min-width: 80px;
}

.color-presets {
    display: flex;
    gap: 0.5rem;
}

.color-preset {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.color-preset:hover {
    border-color: #3498db;
    transform: scale(1.1);
}

/* 使用统计 */
.stats-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #2c3e50;
    line-height: 1;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.recent-articles h4 {
    color: #495057;
    margin-bottom: 1rem;
}

.article-list {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.article-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.article-item:last-child {
    border-bottom: none;
}

.article-title a {
    color: #3498db;
    text-decoration: none;
    font-weight: 500;
}

.article-title a:hover {
    text-decoration: underline;
}

.article-date {
    color: #6c757d;
    font-size: 0.85rem;
}

.view-all {
    text-align: center;
}

/* 标签预览 */
.preview-container {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 2rem;
}

.tag-display {
    display: inline-block;
}

.tag-item {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 15px;
    color: white;
    font-size: 0.9rem;
    font-weight: 500;
}

.preview-styles h4 {
    color: #495057;
    margin-bottom: 1rem;
}

.style-variants {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.variant {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.variant-label {
    display: block;
    margin-bottom: 0.5rem;
    color: #6c757d;
    font-size: 0.8rem;
}

.tag-variant {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
    font-weight: 500;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.tag-default {
    color: white;
}

.tag-outline {
    background: transparent !important;
    border: 2px solid;
}

.tag-rounded {
    color: white;
    border-radius: 20px;
}

/* 表单操作 */
.form-actions {
    padding: 2rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-1px);
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-info:hover {
    background: #138496;
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-warning:hover {
    background: #e67e22;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-outline {
    background: transparent;
    border: 1px solid #dee2e6;
    color: #495057;
}

.btn-outline:hover {
    background: #f8f9fa;
    border-color: #6c757d;
}

/* 响应式 */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-section {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-actions .btn {
        justify-content: center;
    }
    
    .color-picker-group {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .stats-info {
        grid-template-columns: 1fr;
    }
    
    .style-variants {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// 表单JavaScript功能
document.addEventListener('DOMContentLoaded', function() {
    initForm();
    bindEvents();
    updatePreview();
});

function initForm() {
    const colorInput = document.getElementById('color');
    const colorPreview = document.getElementById('colorPreview');
    const colorValue = document.querySelector('.color-value');
    
    colorInput.addEventListener('change', function() {
        colorPreview.style.backgroundColor = this.value;
        colorValue.textContent = this.value;
        updatePreview();
    });
    
    colorPreview.addEventListener('click', function() {
        colorInput.click();
    });
}

function bindEvents() {
    const nameInput = document.getElementById('name');
    
    nameInput.addEventListener('input', function() {
        updatePreview();
    });
}

function updatePreview() {
    const name = document.getElementById('name').value || '标签名称';
    const color = document.getElementById('color').value;
    
    // 更新主预览
    const tagPreview = document.querySelector('#tagPreview .tag-item');
    if (tagPreview) {
        tagPreview.textContent = name;
        tagPreview.style.backgroundColor = color;
    }
    
    // 更新样式变种预览
    const previewDefault = document.getElementById('previewDefault');
    const previewOutline = document.getElementById('previewOutline');
    const previewRounded = document.getElementById('previewRounded');
    
    if (previewDefault) {
        previewDefault.textContent = name;
        previewDefault.style.backgroundColor = color;
    }
    
    if (previewOutline) {
        previewOutline.textContent = name;
        previewOutline.style.borderColor = color;
        previewOutline.style.color = color;
    }
    
    if (previewRounded) {
        previewRounded.textContent = name;
        previewRounded.style.backgroundColor = color;
    }
}

function selectPresetColor(color) {
    document.getElementById('color').value = color;
    document.getElementById('colorPreview').style.backgroundColor = color;
    document.querySelector('.color-value').textContent = color;
    updatePreview();
}

function previewTag() {
    const slug = document.getElementById('slug').value;
    if (slug) {
        const previewUrl = `../public/tag.php?slug=${slug}`;
        window.open(previewUrl, '_blank');
    } else {
        alert('标签别名为空，无法预览');
    }
}

function deleteTag() {
    if (confirm('确定要删除这个标签吗？此操作不可恢复。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'tags.php';
        form.style.display = 'none';
        form.innerHTML = `
            <input name="action" value="delete">
            <input name="id" value="<?php echo $tagId; ?>">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 表单验证
document.getElementById('tagForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    
    if (!name) {
        e.preventDefault();
        alert('请输入标签名称');
        document.getElementById('name').focus();
        return false;
    }
    
    if (name.length > 50) {
        e.preventDefault();
        alert('标签名称不能超过50个字符');
        document.getElementById('name').focus();
        return false;
    }
    
    return true;
});

// 键盘快捷键
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('tagForm').submit();
    }
});
</script>