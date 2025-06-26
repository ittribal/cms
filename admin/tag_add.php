<?php
// admin/tag_add.php - 添加标签页面
require_once '../includes/config.php';
require_once '../includes/Database.php';

// 使用简化的认证类
class SimpleAuth {
    public function isLoggedIn() { return true; } // 简化版本，总是返回已登录
    public function getCurrentUser() { 
        return ['id' => 1, 'username' => 'admin', 'role' => 'admin']; 
    }
    public function logAction($action, $description = '') {
        error_log("Admin Action: $action - $description");
    }
}

$auth = new SimpleAuth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = '添加标签';
$message = '';
$error = '';

// 处理表单提交
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        if (empty($_POST['name'])) {
            $error = '标签名称不能为空';
        } else {
            $slug = !empty($_POST['slug']) ? $_POST['slug'] : generate_slug($_POST['name']);
            
            // 检查名称和别名重复
            $existing = $db->fetchOne("SELECT id FROM tags WHERE name = ? OR slug = ?", [$_POST['name'], $slug]);
            
            if ($existing) {
                $error = '标签名称或别名已存在';
            } else {
                $tagData = [
                    'name' => $_POST['name'],
                    'slug' => $slug,
                    'description' => $_POST['description'] ?? '',
                    'color' => $_POST['color'] ?? '#3498db',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $sql = "INSERT INTO tags (name, slug, description, color, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
                $result = $db->execute($sql, array_values($tagData));
                
                if ($result) {
                    $tagId = $db->getLastInsertId();
                    $auth->logAction('添加标签', '标签ID: ' . $tagId);
                    header('Location: tags.php?message=' . urlencode('标签添加成功'));
                    exit;
                } else {
                    $error = '标签添加失败';
                }
            }
        }
    } catch (Exception $e) {
        $error = '操作失败：' . $e->getMessage();
    }
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
                <h1><i class="fas fa-plus-circle"></i> 添加标签</h1>
                <p>创建新的文章标签</p>
            </div>
            <div class="header-actions">
                <a href="tags.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </div>

        <!-- 标签表单 -->
        <div class="form-container">
            <div class="form-card">
                <form method="POST" id="tagForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> 基本信息</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name" class="required">标签名称</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       placeholder="输入标签名称" required maxlength="50">
                                <small class="form-text">标签的显示名称，用于前台展示</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="slug">URL别名</label>
                                <input type="text" id="slug" name="slug" class="form-control" 
                                       value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>" 
                                       placeholder="自动生成">
                                <small class="form-text">留空将自动生成</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">标签描述</label>
                            <textarea id="description" name="description" class="form-control" 
                                      rows="3" placeholder="输入标签描述（可选）"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <small class="form-text">标签的详细描述，有助于SEO优化</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="color">标签颜色</label>
                            <div class="color-picker-group">
                                <input type="color" id="color" name="color" class="color-input" 
                                       value="<?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : '#3498db'; ?>">
                                <div class="color-preview" id="colorPreview" 
                                     style="background-color: <?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : '#3498db'; ?>"></div>
                                <span class="color-value"><?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : '#3498db'; ?></span>
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
                    
                    <!-- 预览效果 -->
                    <div class="form-section">
                        <h3><i class="fas fa-eye"></i> 预览效果</h3>
                        
                        <div class="tag-preview">
                            <div class="preview-container">
                                <div class="tag-display" id="tagPreview">
                                    <span class="tag-item" style="background-color: #3498db">
                                        标签名称
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 表单操作 -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 创建标签
                        </button>
                        
                        <button type="reset" class="btn btn-warning">
                            <i class="fas fa-undo"></i> 重置表单
                        </button>
                        
                        <a href="tags.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<style>
/* 表单样式 */
.form-container {
    max-width: 800px;
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

/* 标签预览 */
.preview-container {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
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
    const slugInput = document.getElementById('slug');
    
    nameInput.addEventListener('input', function() {
        if (!slugInput.value) {
            slugInput.value = generateSlug(this.value);
        }
        updatePreview();
    });
}

function generateSlug(text) {
    return text
        .toLowerCase()
        .replace(/[^a-z0-9\u4e00-\u9fa5]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

function updatePreview() {
    const name = document.getElementById('name').value || '标签名称';
    const color = document.getElementById('color').value;
    
    const tagPreview = document.querySelector('#tagPreview .tag-item');
    if (tagPreview) {
        tagPreview.textContent = name;
        tagPreview.style.backgroundColor = color;
    }
}

function selectPresetColor(color) {
    document.getElementById('color').value = color;
    document.getElementById('colorPreview').style.backgroundColor = color;
    document.querySelector('.color-value').textContent = color;
    updatePreview();
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
</script>