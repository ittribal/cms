<?php
// admin/category_form.php - 分类表单页面

// 获取分类ID（编辑时）
$categoryId = $_GET['id'] ?? 0;
$parentId = $_GET['parent_id'] ?? 0;
$category = null;

// 如果是编辑模式，获取分类信息
if ($categoryId && $action === 'edit') {
    $category = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);
    if (!$category) {
        header('Location: categories.php?error=' . urlencode('分类不存在'));
        exit;
    }
}

// 获取所有分类用于父分类选择
$allCategories = $db->fetchAll("
    SELECT id, name, parent_id, level 
    FROM categories 
    ORDER BY sort_order ASC, name ASC
");

// 构建分类树选项
function buildCategoryOptions($categories, $selectedId = null, $excludeId = null) {
    $options = '<option value="">-- 选择父分类 --</option>';
    
    foreach ($categories as $cat) {
        // 排除自己（编辑时不能选择自己作为父分类）
        if ($excludeId && $cat['id'] == $excludeId) {
            continue;
        }
        
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $cat['level'] ?? 0);
        $selected = ($selectedId && $cat['id'] == $selectedId) ? 'selected' : '';
        
        $options .= "<option value=\"{$cat['id']}\" {$selected}>{$indent}{$cat['name']}</option>";
    }
    
    return $options;
}

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
                <h1>
                    <i class="fas fa-folder<?php echo $action === 'edit' ? '-open' : '-plus'; ?>"></i> 
                    <?php echo $action === 'edit' ? '编辑分类' : '新建分类'; ?>
                </h1>
                <p><?php echo $action === 'edit' ? '编辑分类信息和设置' : '创建新的文章分类'; ?></p>
            </div>
            <div class="header-actions">
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </div>

        <!-- 分类表单 -->
        <div class="form-container">
            <form method="POST" class="category-form" id="categoryForm">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($category): ?>
                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                <?php endif; ?>
                
                <div class="form-sections">
                    <!-- 基本信息 -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> 基本信息</h3>
                        
                        <div class="form-row">
                            <div class="form-group col-8">
                                <label for="name" class="required">分类名称</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" 
                                       placeholder="输入分类名称" required maxlength="100">
                                <small class="form-text">分类的显示名称，用于前台展示</small>
                            </div>
                            
                            <div class="form-group col-4">
                                <label for="slug">URL别名</label>
                                <input type="text" id="slug" name="slug" class="form-control" 
                                       value="<?php echo htmlspecialchars($category['slug'] ?? ''); ?>" 
                                       placeholder="自动生成">
                                <small class="form-text">留空将自动生成</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">分类描述</label>
                            <textarea id="description" name="description" class="form-control" 
                                      rows="3" placeholder="输入分类描述（可选）"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                            <small class="form-text">分类的详细描述，有助于SEO优化</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="parent_id">父分类</label>
                                <select name="parent_id" id="parent_id" class="form-control">
                                    <?php 
                                    $selectedParentId = $parentId ?: ($category['parent_id'] ?? '');
                                    echo buildCategoryOptions($allCategories, $selectedParentId, $categoryId); 
                                    ?>
                                </select>
                                <small class="form-text">选择父分类可创建多级分类结构</small>
                            </div>
                            
                            <div class="form-group col-3">
                                <label for="sort_order">排序</label>
                                <input type="number" id="sort_order" name="sort_order" class="form-control" 
                                       value="<?php echo htmlspecialchars($category['sort_order'] ?? '0'); ?>" 
                                       min="0" max="9999">
                                <small class="form-text">数字越小越靠前</small>
                            </div>
                            
                            <div class="form-group col-3">
                                <label for="status">状态</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="active" <?php echo (!$category || $category['status'] === 'active') ? 'selected' : ''; ?>>启用</option>
                                    <option value="inactive" <?php echo ($category && $category['status'] === 'inactive') ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 外观设置 -->
                    <div class="form-section">
                        <h3><i class="fas fa-palette"></i> 外观设置</h3>
                        
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="color">分类颜色</label>
                                <div class="color-picker-group">
                                    <input type="color" id="color" name="color" class="color-input" 
                                           value="<?php echo htmlspecialchars($category['color'] ?? '#3498db'); ?>">
                                    <div class="color-preview" id="colorPreview" 
                                         style="background-color: <?php echo htmlspecialchars($category['color'] ?? '#3498db'); ?>"></div>
                                    <span class="color-value"><?php echo htmlspecialchars($category['color'] ?? '#3498db'); ?></span>
                                </div>
                                <small class="form-text">用于标识分类的主题色</small>
                            </div>
                            
                            <div class="form-group col-6">
                                <label for="icon">分类图标</label>
                                <div class="icon-picker-group">
                                    <input type="text" id="icon" name="icon" class="form-control" 
                                           value="<?php echo htmlspecialchars($category['icon'] ?? ''); ?>" 
                                           placeholder="如：fas fa-folder" readonly>
                                    <button type="button" class="btn btn-outline" onclick="showIconPicker()">
                                        <i class="fas fa-icons"></i> 选择图标
                                    </button>
                                </div>
                                <small class="form-text">FontAwesome图标类名</small>
                            </div>
                        </div>
                        
                        <!-- 图标选择器 -->
                        <div class="icon-picker" id="iconPicker" style="display: none;">
                            <h4>选择图标</h4>
                            <div class="icon-grid">
                                <?php 
                                $commonIcons = [
                                    'fas fa-folder', 'fas fa-folder-open', 'fas fa-file-alt', 
                                    'fas fa-newspaper', 'fas fa-book', 'fas fa-bookmark',
                                    'fas fa-tag', 'fas fa-tags', 'fas fa-star', 'fas fa-heart',
                                    'fas fa-home', 'fas fa-user', 'fas fa-users', 'fas fa-cog',
                                    'fas fa-camera', 'fas fa-image', 'fas fa-video', 'fas fa-music',
                                    'fas fa-gamepad', 'fas fa-car', 'fas fa-plane', 'fas fa-globe',
                                    'fas fa-shopping-cart', 'fas fa-gift', 'fas fa-coffee', 'fas fa-pizza-slice'
                                ];
                                
                                foreach ($commonIcons as $iconClass): 
                                ?>
                                    <div class="icon-option" data-icon="<?php echo $iconClass; ?>" 
                                         onclick="selectIcon('<?php echo $iconClass; ?>')">
                                        <i class="<?php echo $iconClass; ?>"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO设置 -->
                    <div class="form-section">
                        <h3><i class="fas fa-search"></i> SEO设置</h3>
                        
                        <div class="form-group">
                            <label for="meta_title">SEO标题</label>
                            <input type="text" id="meta_title" name="meta_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($category['meta_title'] ?? ''); ?>" 
                                   placeholder="留空使用分类名称" maxlength="60">
                            <div class="char-counter">
                                <span id="metaTitleCount">0</span>/60 字符
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="meta_description">SEO描述</label>
                            <textarea id="meta_description" name="meta_description" class="form-control" 
                                      rows="3" placeholder="输入页面描述，有助于搜索引擎优化" 
                                      maxlength="160"><?php echo htmlspecialchars($category['meta_description'] ?? ''); ?></textarea>
                            <div class="char-counter">
                                <span id="metaDescCount">0</span>/160 字符
                            </div>
                        </div>
                        
                        <!-- SEO预览 -->
                        <div class="seo-preview">
                            <h4><i class="fas fa-eye"></i> 搜索结果预览</h4>
                            <div class="preview-result">
                                <div class="preview-title" id="previewTitle">
                                    <?php echo htmlspecialchars($category['meta_title'] ?? $category['name'] ?? '分类标题'); ?>
                                </div>
                                <div class="preview-url" id="previewUrl">
                                    <?php echo SITE_URL ?? 'https://example.com'; ?>/category/<?php echo htmlspecialchars($category['slug'] ?? 'category-slug'); ?>
                                </div>
                                <div class="preview-description" id="previewDescription">
                                    <?php echo htmlspecialchars($category['meta_description'] ?? $category['description'] ?? '分类描述会显示在这里...'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 表单操作 -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action === 'edit' ? '更新分类' : '创建分类'; ?>
                    </button>
                    
                    <?php if ($action === 'edit'): ?>
                        <button type="button" class="btn btn-info" onclick="previewCategory()">
                            <i class="fas fa-eye"></i> 预览分类
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-warning" onclick="saveDraft()">
                        <i class="fas fa-save"></i> 保存草稿
                    </button>
                    
                    <a href="categories.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 取消
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<style>
/* 分类表单样式 */
.form-container {
    max-width: 1000px;
    margin: 0 auto;
}

.category-form {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.form-sections {
    padding: 0;
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
    grid-template-columns: repeat(12, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.col-3 { grid-column: span 3; }
.col-4 { grid-column: span 4; }
.col-6 { grid-column: span 6; }
.col-8 { grid-column: span 8; }
.col-12 { grid-column: span 12; }

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
}

/* 图标选择器 */
.icon-picker-group {
    display: flex;
    gap: 0.5rem;
}

.icon-picker-group input {
    flex: 1;
}

.icon-picker {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
}

.icon-picker h4 {
    margin-bottom: 1rem;
    color: #495057;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
}

.icon-option {
    width: 50px;
    height: 50px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: white;
    transition: all 0.2s ease;
    font-size: 1.2rem;
}

.icon-option:hover,
.icon-option.selected {
    border-color: #3498db;
    background: #e3f2fd;
    color: #3498db;
    transform: scale(1.05);
}

/* 字符计数器 */
.char-counter {
    text-align: right;
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.char-counter.warning {
    color: #f39c12;
}

.char-counter.danger {
    color: #e74c3c;
}

/* SEO预览 */
.seo-preview {
    margin-top: 1.5rem;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
}

.seo-preview h4 {
    margin-bottom: 1rem;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.preview-result {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.preview-title {
    color: #1a0dab;
    font-size: 1.1rem;
    font-weight: 500;
    text-decoration: underline;
    margin-bottom: 0.25rem;
}

.preview-url {
    color: #006621;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.preview-description {
    color: #545454;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* 表单操作 */
.form-actions {
    padding: 2rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
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
    
    .col-3, .col-4, .col-6, .col-8 {
        grid-column: span 12;
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
        flex-wrap: wrap;
    }
    
    .icon-picker-group {
        flex-direction: column;
    }
}
</style>

<script>
// 分类表单JavaScript功能

document.addEventListener('DOMContentLoaded', function() {
    // 初始化表单
    initForm();
    
    // 绑定事件
    bindEvents();
    
    // 更新字符计数
    updateCharCounters();
    
    // 更新SEO预览
    updateSEOPreview();
});

function initForm() {
    // 设置颜色选择器
    const colorInput = document.getElementById('color');
    const colorPreview = document.getElementById('colorPreview');
    const colorValue = document.querySelector('.color-value');
    
    colorInput.addEventListener('change', function() {
        colorPreview.style.backgroundColor = this.value;
        colorValue.textContent = this.value;
    });
    
    // 点击预览色块也能打开颜色选择器
    colorPreview.addEventListener('click', function() {
        colorInput.click();
    });
}

function bindEvents() {
    // 自动生成URL别名
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    nameInput.addEventListener('input', function() {
        if (!slugInput.value) {
            slugInput.value = generateSlug(this.value);
        }
        updateSEOPreview();
    });
    
    // 字符计数
    const metaTitleInput = document.getElementById('meta_title');
    const metaDescInput = document.getElementById('meta_description');
    
    metaTitleInput.addEventListener('input', function() {
        updateCharCounter('metaTitleCount', this.value.length, 60);
        updateSEOPreview();
    });
    
    metaDescInput.addEventListener('input', function() {
        updateCharCounter('metaDescCount', this.value.length, 160);
        updateSEOPreview();
    });
    
    // URL别名变化时更新预览
    slugInput.addEventListener('input', updateSEOPreview);
    
    // 描述变化时更新预览
    document.getElementById('description').addEventListener('input', updateSEOPreview);
}

function generateSlug(text) {
    return text
        .toLowerCase()
        .replace(/[^a-z0-9\u4e00-\u9fa5]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

function updateCharCounter(counterId, length, max) {
    const counter = document.getElementById(counterId);
    const parent = counter.parentElement;
    
    counter.textContent = length;
    
    parent.classList.remove('warning', 'danger');
    if (length > max * 0.8) {
        parent.classList.add('warning');
    }
    if (length > max) {
        parent.classList.add('danger');
    }
}

function updateCharCounters() {
    const metaTitle = document.getElementById('meta_title').value;
    const metaDesc = document.getElementById('meta_description').value;
    
    updateCharCounter('metaTitleCount', metaTitle.length, 60);
    updateCharCounter('metaDescCount', metaDesc.length, 160);
}

function updateSEOPreview() {
    const name = document.getElementById('name').value;
    const slug = document.getElementById('slug').value;
    const metaTitle = document.getElementById('meta_title').value;
    const metaDesc = document.getElementById('meta_description').value;
    const description = document.getElementById('description').value;
    
    // 更新预览标题
    const previewTitle = document.getElementById('previewTitle');
    previewTitle.textContent = metaTitle || name || '分类标题';
    
    // 更新预览URL
    const previewUrl = document.getElementById('previewUrl');
    const baseUrl = '<?php echo SITE_URL ?? "https://example.com"; ?>';
    previewUrl.textContent = `${baseUrl}/category/${slug || 'category-slug'}`;
    
    // 更新预览描述
    const previewDesc = document.getElementById('previewDescription');
    previewDesc.textContent = metaDesc || description || '分类描述会显示在这里...';
}

// 显示图标选择器
function showIconPicker() {
    const iconPicker = document.getElementById('iconPicker');
    iconPicker.style.display = iconPicker.style.display === 'none' ? 'block' : 'none';
}

// 选择图标
function selectIcon(iconClass) {
    document.getElementById('icon').value = iconClass;
    
    // 更新选中状态
    document.querySelectorAll('.icon-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    document.querySelector(`[data-icon="${iconClass}"]`).classList.add('selected');
    
    // 隐藏选择器
    document.getElementById('iconPicker').style.display = 'none';
}

// 预览分类
function previewCategory() {
    const slug = document.getElementById('slug').value;
    if (slug) {
        const previewUrl = `../public/category.php?slug=${slug}`;
        window.open(previewUrl, '_blank');
    } else {
        alert('请先保存分类后再预览');
    }
}

// 保存草稿
function saveDraft() {
    // 这里可以实现自动保存功能
    alert('草稿保存功能开发中...');
}

// 表单验证
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    
    if (!name) {
        e.preventDefault();
        alert('请输入分类名称');
        document.getElementById('name').focus();
        return false;
    }
    
    if (name.length > 100) {
        e.preventDefault();
        alert('分类名称不能超过100个字符');
        document.getElementById('name').focus();
        return false;
    }
    
    // 检查SEO标题长度
    const metaTitle = document.getElementById('meta_title').value;
    if (metaTitle.length > 60) {
        if (!confirm('SEO标题超过推荐长度(60字符)，是否继续保存？')) {
            e.preventDefault();
            return false;
        }
    }
    
    // 检查SEO描述长度
    const metaDesc = document.getElementById('meta_description').value;
    if (metaDesc.length > 160) {
        if (!confirm('SEO描述超过推荐长度(160字符)，是否继续保存？')) {
            e.preventDefault();
            return false;
        }
    }
    
    return true;
});

// 自动保存功能（可选）
let autoSaveTimer;
function startAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        saveDraft();
    }, 30000); // 30秒后自动保存
}

// 监听表单变化，启动自动保存
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('input', startAutoSave);
});
</script>