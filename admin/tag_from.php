<?php
// admin/tag_form.php - 标签表单页面

// 获取标签数据（编辑时）
$tag = null;
if ($action === 'edit') {
    $tagId = $_GET['id'] ?? 0;
    if ($tagId) {
        $tag = $db->fetchOne("SELECT * FROM tags WHERE id = ?", [$tagId]);
        if (!$tag) {
            header('Location: tags.php?error=' . urlencode('标签不存在'));
            exit;
        }
    } else {
        header('Location: tags.php?error=' . urlencode('无效的标签ID'));
        exit;
    }
}

// 包含头部模板
include '../templates/admin_header.php';
?>

<!-- 标签表单页面内容 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* 标签表单专用样式 */
.tag-form-page {
    padding: 0;
    background: transparent;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: none;
    font-weight: 500;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.alert-error {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.page-header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    max-width: 1000px;
    margin: 0 auto;
}

.page-title {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.page-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 1rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.btn-secondary {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #495057;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(168, 237, 234, 0.4);
    text-decoration: none;
    color: #495057;
}

.form-container {
    max-width: 1000px;
    margin: 0 auto;
}

.form-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.form-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
}

.section-title {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: #495057;
    font-weight: 600;
    font-size: 0.95rem;
}

.form-label.required::after {
    content: ' *';
    color: #e74c3c;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #fafbfc;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-text {
    color: #6c757d;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    display: block;
    line-height: 1.4;
}

.color-picker-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.color-input-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.color-input {
    width: 50px;
    height: 50px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    background: none;
    transition: all 0.3s ease;
}

.color-input:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.color-preview {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    border: 2px solid #e9ecef;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.color-hex-input {
    flex: 1;
    font-family: 'Monaco', 'Menlo', monospace;
    text-align: center;
    font-weight: 600;
}

.color-presets {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.preset-color {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
}

.preset-color:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.preset-color.active {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
}

.preset-color::after {
    content: attr(title);
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    color: #6c757d;
    white-space: nowrap;
}

.preview-section {
    text-align: center;
}

.tag-preview-container {
    padding: 2rem;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 15px;
    border: 2px dashed #dee2e6;
    margin-bottom: 1.5rem;
}

.tag-preview {
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-size: 1.1rem;
    font-weight: 600;
    display: inline-block;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    transform-origin: center;
}

.tag-preview:hover {
    transform: scale(1.05);
}

.stats-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-3px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.form-actions {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    flex-wrap: wrap;
}

.btn-primary {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    color: #721c24;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 154, 158, 0.4);
}

/* 响应式设计 */
@media (max-width: 768px) {
    .page-header-content {
        flex-direction: column;
        gap: 1.5rem;
        text-align: center;
    }
    
    .header-actions {
        justify-content: center;
        width: 100%;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .color-picker-section {
        grid-template-columns: 1fr;
    }
    
    .color-presets {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}

/* 动画效果 */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tag-form-page > * {
    animation: fadeInUp 0.6s ease-out forwards;
}

.tag-form-page > *:nth-child(1) { animation-delay: 0.1s; }
.tag-form-page > *:nth-child(2) { animation-delay: 0.2s; }
.tag-form-page > *:nth-child(3) { animation-delay: 0.3s; }
.tag-form-page > *:nth-child(4) { animation-delay: 0.4s; }
</style>

<div class="tag-form-page">
    <!-- 消息提示 -->
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

    <!-- 页面头部 -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-info">
                <h1 class="page-title">
                    <i class="fas fa-tag"></i> 
                    <?php echo $action === 'add' ? '新建标签' : '编辑标签'; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo $action === 'add' ? '创建一个新的文章标签来组织内容' : '修改标签信息和外观设置'; ?>
                </p>
            </div>
            <div class="header-actions">
                <a href="tags.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </div>
    </div>

    <!-- 标签表单 -->
    <div class="form-container">
        <form method="POST" id="tagForm">
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
            <?php endif; ?>

            <div class="form-grid">
                <!-- 基本信息 -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i> 基本信息
                    </h3>
                    
                    <div class="form-group">
                        <label for="name" class="form-label required">标签名称</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($tag['name'] ?? ''); ?>" 
                               placeholder="输入标签名称（如：前端开发）" required maxlength="50">
                        <small class="form-text">标签的显示名称，最多50个字符，建议简洁明了</small>
                    </div>

                    <div class="form-group">
                        <label for="slug" class="form-label">URL别名</label>
                        <input type="text" id="slug" name="slug" class="form-control" 
                               value="<?php echo htmlspecialchars($tag['slug'] ?? ''); ?>" 
                               placeholder="自动生成或手动输入" maxlength="50">
                        <small class="form-text">用于URL的友好名称，留空将根据标签名称自动生成</small>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">标签描述</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="4" placeholder="描述这个标签的用途、含义或使用场景..."><?php echo htmlspecialchars($tag['description'] ?? ''); ?></textarea>
                        <small class="form-text">可选项，帮助其他用户理解这个标签的具体用途</small>
                    </div>
                </div>

                <!-- 外观设置 -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-palette"></i> 外观设置
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">标签颜色</label>
                        <div class="color-picker-section">
                            <div class="color-input-group">
                                <input type="color" id="color" name="color" class="color-input" 
                                       value="<?php echo htmlspecialchars($tag['color'] ?? '#3498db'); ?>">
                                <div class="color-preview" id="colorPreview" 
                                     style="background-color: <?php echo htmlspecialchars($tag['color'] ?? '#3498db'); ?>"></div>
                            </div>
                            <div>
                                <input type="text" id="colorHex" class="form-control color-hex-input" 
                                       value="<?php echo htmlspecialchars($tag['color'] ?? '#3498db'); ?>" 
                                       pattern="^#[0-9A-Fa-f]{6}$" placeholder="#3498db">
                            </div>
                        </div>
                        <small class="form-text">选择标签的显示颜色，或直接输入十六进制颜色代码</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">颜色预设</label>
                        <div class="color-presets">
                            <div class="preset-color" data-color="#3498db" style="background: #3498db;" title="蓝色"></div>
                            <div class="preset-color" data-color="#e74c3c" style="background: #e74c3c;" title="红色"></div>
                            <div class="preset-color" data-color="#2ecc71" style="background: #2ecc71;" title="绿色"></div>
                            <div class="preset-color" data-color="#f39c12" style="background: #f39c12;" title="橙色"></div>
                            <div class="preset-color" data-color="#9b59b6" style="background: #9b59b6;" title="紫色"></div>
                            <div class="preset-color" data-color="#1abc9c" style="background: #1abc9c;" title="青色"></div>
                            <div class="preset-color" data-color="#34495e" style="background: #34495e;" title="深灰"></div>
                            <div class="preset-color" data-color="#95a5a6" style="background: #95a5a6;" title="浅灰"></div>
                        </div>
                        <small class="form-text">点击选择预设颜色，或使用上方的颜色选择器自定义</small>
                    </div>

                    <div class="preview-section">
                        <label class="form-label">实时预览</label>
                        <div class="tag-preview-container">
                            <span class="tag-preview" id="tagPreview">
                                <?php echo htmlspecialchars($tag['name'] ?? '标签预览'); ?>
                            </span>
                        </div>
                        <small class="form-text">实时预览标签的最终显示效果</small>
                    </div>
                </div>
            </div>

            <?php if ($action === 'edit' && $tag): ?>
                <!-- 使用统计 -->
                <div class="stats-section">
                    <h3 class="section-title">
                        <i class="fas fa-chart-bar"></i> 使用统计
                    </h3>
                    
                    <div class="stats-grid">
                        <?php
                        $articleCount = $db->fetchOne("SELECT COUNT(*) as count FROM article_tags WHERE tag_id = ?", [$tag['id']])['count'];
                        $lastUsed = $db->fetchOne("SELECT MAX(a.created_at) as last_used FROM articles a JOIN article_tags at ON a.id = at.article_id WHERE at.tag_id = ?", [$tag['id']])['last_used'];
                        ?>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($articleCount); ?></div>
                            <div class="stat-label">关联文章</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-number">
                                <?php echo $lastUsed ? date('m-d', strtotime($lastUsed)) : '未使用'; ?>
                            </div>
                            <div class="stat-label">最后使用</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number"><?php echo date('m-d', strtotime($tag['created_at'])); ?></div>
                            <div class="stat-label">创建时间</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 操作按钮 -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> 
                    <?php echo $action === 'add' ? '创建标签' : '保存修改'; ?>
                </button>
                <a href="tags.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> 取消
                </a>
                
                <?php if ($action === 'edit' && $articleCount === 0): ?>
                    <button type="button" onclick="deleteCurrentTag()" class="btn btn-danger">
                        <i class="fas fa-trash"></i> 删除标签
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const colorInput = document.getElementById('color');
    const colorHex = document.getElementById('colorHex');
    const colorPreview = document.getElementById('colorPreview');
    const tagPreview = document.getElementById('tagPreview');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('tagForm');

    // 自动生成slug
    nameInput.addEventListener('input', function() {
        if (!slugInput.value || slugInput.dataset.auto !== 'false') {
            const slug = generateSlug(this.value);
            slugInput.value = slug;
            slugInput.dataset.auto = 'true';
        }
        updateTagPreview();
    });

    // 手动编辑slug时停止自动生成
    slugInput.addEventListener('input', function() {
        slugInput.dataset.auto = 'false';
    });

    // 颜色选择器同步
    colorInput.addEventListener('input', function() {
        colorHex.value = this.value;
        updateColorPreview(this.value);
        updateTagPreview();
        updateActivePreset(this.value);
    });

    colorHex.addEventListener('input', function() {
        if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
            colorInput.value = this.value;
            updateColorPreview(this.value);
            updateTagPreview();
            updateActivePreset(this.value);
        }
    });

    // 颜色预设选择
    document.querySelectorAll('.preset-color').forEach(preset => {
        preset.addEventListener('click', function() {
            const color = this.dataset.color;
            colorInput.value = color;
            colorHex.value = color;
            updateColorPreview(color);
            updateTagPreview();
            updateActivePreset(color);
        });
    });

    // 更新颜色预览
    function updateColorPreview(color) {
        colorPreview.style.backgroundColor = color;
    }

    // 更新标签预览
    function updateTagPreview() {
        const name = nameInput.value || '标签预览';
        const color = colorInput.value;
        
        tagPreview.textContent = name;
        tagPreview.style.backgroundColor = color;
        
        // 添加脉冲动画效果
        tagPreview.style.animation = 'none';
        tagPreview.offsetHeight; // 强制重排
        tagPreview.style.animation = 'pulse 0.6s ease-in-out';
    }

    // 更新活动预设
    function updateActivePreset(color) {
        document.querySelectorAll('.preset-color').forEach(preset => {
            preset.classList.remove('active');
            if (preset.dataset.color === color) {
                preset.classList.add('active');
            }
        });
    }

    // 生成slug
    function generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/[^a-zA-Z0-9\u4e00-\u9fa5\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    // 表单验证和提交
    form.addEventListener('submit', function(e) {
        const name = nameInput.value.trim();
        const color = colorHex.value;

        if (!name) {
            e.preventDefault();
            alert('请输入标签名称');
            nameInput.focus();
            return;
        }

        if (!color.match(/^#[0-9A-Fa-f]{6}$/)) {
            e.preventDefault();
            alert('请选择有效的颜色');
            colorHex.focus();
            return;
        }

        // 显示加载状态
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        
        // 添加表单提交动画
        form.style.opacity = '0.7';
        form.style.pointerEvents = 'none';
    });

    // 初始化
    updateTagPreview();
    updateActivePreset(colorInput.value);
    
    // 自动隐藏消息提示
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            alert.style.transition = 'all 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    // 添加脉冲动画CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
});

// 删除当前标签
function deleteCurrentTag() {
    if (confirm('确定要删除这个标签吗？删除后无法恢复，且会同时删除与文章的关联。')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'tags.php';
        form.style.display = 'none';
        form.innerHTML = `
            <input name="action" value="delete">
            <input name="id" value="<?php echo $tag['id'] ?? ''; ?>">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// 包含底部模板
include '../templates/admin_footer.php';
?>