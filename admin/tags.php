<?php
// admin/tags.php - 标签管理
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = '标签管理';
$action = $_GET['action'] ?? 'list';
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// 处理操作
if ($_POST) {
    $postAction = $_POST['action'] ?? '';
    
    switch ($postAction) {
        case 'add':
        case 'edit':
            $result = handleTagForm($_POST, $postAction);
            if ($result['success']) {
                $message = $result['message'];
                if ($postAction === 'add') {
                    header('Location: tags.php?message=' . urlencode($message));
                    exit;
                }
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'delete':
            $result = deleteTag($_POST['id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'batch_delete':
            $result = batchDeleteTags($_POST['ids']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'clean_unused':
            $result = cleanUnusedTags();
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// 处理标签表单
function handleTagForm($data, $action) {
    global $db, $auth;
    
    try {
        if (empty($data['name'])) {
            return ['success' => false, 'message' => '标签名称不能为空'];
        }
        
        $slug = !empty($data['slug']) ? $data['slug'] : generateSlug($data['name']);
        
        // 检查名称和别名重复
        $existingSql = "SELECT id FROM tags WHERE (name = ? OR slug = ?) AND id != ?";
        $existingId = $action === 'edit' ? $data['id'] : 0;
        $existing = $db->fetchOne($existingSql, [$data['name'], $slug, $existingId]);
        
        if ($existing) {
            return ['success' => false, 'message' => '标签名称或别名已存在'];
        }
        
        $tagData = [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'color' => $data['color'] ?? '#3498db',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($action === 'add') {
            $tagData['created_at'] = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO tags (" . implode(', ', array_keys($tagData)) . ") 
                    VALUES (" . str_repeat('?,', count($tagData) - 1) . "?)";
            $result = $db->execute($sql, array_values($tagData));
            
            if ($result) {
                $tagId = $db->getLastInsertId();
                return ['success' => true, 'message' => '标签添加成功'];
            }
        } else {
            $tagId = $data['id'];
            $setClause = implode(' = ?, ', array_keys($tagData)) . ' = ?';
            $sql = "UPDATE tags SET {$setClause} WHERE id = ?";
            $params = array_merge(array_values($tagData), [$tagId]);
            
            $result = $db->execute($sql, $params);
            
            if ($result) {
                return ['success' => true, 'message' => '标签更新成功'];
            }
        }
        
        return ['success' => false, 'message' => '操作失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '操作失败：' . $e->getMessage()];
    }
}

// 删除标签
function deleteTag($id) {
    global $db;
    
    try {
        // 删除标签和文章的关联
        $db->execute("DELETE FROM article_tags WHERE tag_id = ?", [$id]);
        
        // 删除标签
        $result = $db->execute("DELETE FROM tags WHERE id = ?", [$id]);
        
        if ($result) {
            return ['success' => true, 'message' => '标签删除成功'];
        }
        
        return ['success' => false, 'message' => '删除失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '删除失败：' . $e->getMessage()];
    }
}

// 批量删除标签
function batchDeleteTags($ids) {
    global $db;
    
    try {
        if (empty($ids) || !is_array($ids)) {
            return ['success' => false, 'message' => '请选择要删除的标签'];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        // 删除标签和文章的关联
        $db->execute("DELETE FROM article_tags WHERE tag_id IN ({$placeholders})", $ids);
        
        // 删除标签
        $result = $db->execute("DELETE FROM tags WHERE id IN ({$placeholders})", $ids);
        
        if ($result) {
            return ['success' => true, 'message' => '批量删除成功，共删除 ' . count($ids) . ' 个标签'];
        }
        
        return ['success' => false, 'message' => '批量删除失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '批量删除失败：' . $e->getMessage()];
    }
}

// 清理未使用的标签
function cleanUnusedTags() {
    global $db;
    
    try {
        // 先查询要删除的标签数量
        $countSql = "SELECT COUNT(*) as count FROM tags WHERE id NOT IN (SELECT DISTINCT tag_id FROM article_tags WHERE tag_id IS NOT NULL)";
        $countResult = $db->fetchOne($countSql);
        $deletedCount = $countResult ? $countResult['count'] : 0;
        
        if ($deletedCount > 0) {
            // 执行删除操作
            $result = $db->execute("DELETE FROM tags WHERE id NOT IN (SELECT DISTINCT tag_id FROM article_tags WHERE tag_id IS NOT NULL)");
            
            if ($result) {
                return ['success' => true, 'message' => "清理完成，删除了 {$deletedCount} 个未使用的标签"];
            } else {
                return ['success' => false, 'message' => '删除操作失败'];
            }
        } else {
            return ['success' => true, 'message' => '没有找到未使用的标签'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '清理失败：' . $e->getMessage()];
    }
}

// 生成URL别名
function generateSlug($text) {
    $slug = preg_replace('/[^a-zA-Z0-9\u4e00-\u9fa5]+/', '-', $text);
    $slug = trim($slug, '-');
    return $slug ?: 'tag-' . time();
}

// 根据操作显示不同页面
switch ($action) {
    case 'add':
    case 'edit':
        // 标签表单页面
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
                        <h1><i class="fas fa-tag"></i> <?php echo $action === 'add' ? '新建标签' : '编辑标签'; ?></h1>
                        <p><?php echo $action === 'add' ? '创建一个新的文章标签' : '修改标签信息'; ?></p>
                    </div>
                    <div class="header-actions">
                        <a href="tags.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>

                <!-- 标签表单 -->
                <div class="content-card">
                    <form method="POST" class="tag-form">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-section">
                                <h3>基本信息</h3>
                                
                                <div class="form-group">
                                    <label for="name" class="required">标签名称</label>
                                    <input type="text" id="name" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($tag['name'] ?? ''); ?>" 
                                           placeholder="输入标签名称" required maxlength="50">
                                    <small class="form-text">标签的显示名称，最多50个字符</small>
                                </div>

                                <div class="form-group">
                                    <label for="slug">URL别名</label>
                                    <input type="text" id="slug" name="slug" class="form-control" 
                                           value="<?php echo htmlspecialchars($tag['slug'] ?? ''); ?>" 
                                           placeholder="自动生成或手动输入" maxlength="50">
                                    <small class="form-text">用于URL的友好名称，留空将自动生成</small>
                                </div>

                                <div class="form-group">
                                    <label for="description">标签描述</label>
                                    <textarea id="description" name="description" class="form-control" 
                                              rows="4" placeholder="描述这个标签的用途和含义"><?php echo htmlspecialchars($tag['description'] ?? ''); ?></textarea>
                                    <small class="form-text">可选，帮助其他用户理解这个标签的用途</small>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>外观设置</h3>
                                
                                <div class="form-group">
                                    <label for="color">标签颜色</label>
                                    <div class="color-picker-group">
                                        <input type="color" id="color" name="color" class="color-input" 
                                               value="<?php echo htmlspecialchars($tag['color'] ?? '#3498db'); ?>">
                                        <div class="color-preview" id="colorPreview" 
                                             style="background-color: <?php echo htmlspecialchars($tag['color'] ?? '#3498db'); ?>"></div>
                                        <input type="text" id="colorHex" class="color-hex" 
                                               value="<?php echo htmlspecialchars($tag['color'] ?? '#3498db'); ?>" 
                                               pattern="^#[0-9A-Fa-f]{6}$" placeholder="#3498db">
                                    </div>
                                    <small class="form-text">选择标签的显示颜色</small>
                                </div>

                                <div class="form-group">
                                    <label>颜色预设</label>
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
                                </div>

                                <div class="form-group">
                                    <label>标签预览</label>
                                    <div class="tag-preview">
                                        <span class="tag-badge" id="tagPreview">
                                            <?php echo htmlspecialchars($tag['name'] ?? '标签预览'); ?>
                                        </span>
                                    </div>
                                    <small class="form-text">实时预览标签的显示效果</small>
                                </div>
                            </div>
                        </div>

                        <?php if ($action === 'edit' && $tag): ?>
                            <div class="form-section">
                                <h3>使用统计</h3>
                                
                                <div class="stats-row">
                                    <?php
                                    $articleCount = $db->fetchOne("SELECT COUNT(*) as count FROM article_tags WHERE tag_id = ?", [$tag['id']])['count'];
                                    $lastUsed = $db->fetchOne("SELECT MAX(a.created_at) as last_used FROM articles a JOIN article_tags at ON a.id = at.article_id WHERE at.tag_id = ?", [$tag['id']])['last_used'];
                                    ?>
                                    
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-number"><?php echo number_format($articleCount); ?></div>
                                            <div class="stat-label">关联文章</div>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-number">
                                                <?php echo $lastUsed ? date('Y-m-d', strtotime($lastUsed)) : '未使用'; ?>
                                            </div>
                                            <div class="stat-label">最后使用</div>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-number"><?php echo date('Y-m-d', strtotime($tag['created_at'])); ?></div>
                                            <div class="stat-label">创建时间</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
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
            </main>
        </div>

        <style>
        /* 标签表单样式 - 与分类管理保持一致 */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #495057;
            font-weight: 500;
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
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .color-picker-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .color-input {
            width: 50px;
            height: 40px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            background: none;
        }

        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }

        .color-hex {
            width: 100px;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-family: monospace;
            text-align: center;
        }

        .color-presets {
            display: grid;
            grid-template-columns: repeat(8, 40px);
            gap: 0.5rem;
        }

        .preset-color {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .preset-color:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .preset-color.active {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .tag-preview {
            padding: 1rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .tag-badge {
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 500;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
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
            margin-top: 0.25rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .color-presets {
                grid-template-columns: repeat(4, 40px);
            }
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            const colorInput = document.getElementById('color');
            const colorHex = document.getElementById('colorHex');
            const colorPreview = document.getElementById('colorPreview');
            const tagPreview = document.getElementById('tagPreview');

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
            });

            colorHex.addEventListener('input', function() {
                if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                    colorInput.value = this.value;
                    updateColorPreview(this.value);
                    updateTagPreview();
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
                    
                    // 更新活动状态
                    document.querySelectorAll('.preset-color').forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
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

            // 初始化预览
            updateTagPreview();
            
            // 设置当前颜色为活动状态
            const currentColor = colorInput.value;
            document.querySelectorAll('.preset-color').forEach(preset => {
                if (preset.dataset.color === currentColor) {
                    preset.classList.add('active');
                }
            });
        });

        // 删除当前标签
        function deleteCurrentTag() {
            if (confirm('确定要删除这个标签吗？删除后无法恢复。')) {
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
        break;
        
    default:
        // 标签列表页面
        $search = $_GET['search'] ?? '';
        $page = max(1, $_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // 构建查询
        $where = [];
        $params = [];

        if ($search) {
            $where[] = "(name LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // 获取标签列表
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM article_tags WHERE tag_id = t.id) as article_count
                FROM tags t 
                {$whereClause}
                ORDER BY article_count DESC, t.name ASC 
                LIMIT ? OFFSET ?";

        $tags = $db->fetchAll($sql, array_merge($params, [$limit, $offset]));

        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM tags t {$whereClause}";
        $totalCount = $db->fetchOne($countSql, $params)['total'];
        $totalPages = ceil($totalCount / $limit);

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
                        <h1><i class="fas fa-tags"></i> 标签管理</h1>
                        <p>管理文章标签，支持批量操作和标签合并</p>
                    </div>
                    <div class="header-actions">
                        <a href="tags.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 新建标签
                        </a>
                        <button onclick="showCleanModal()" class="btn btn-info">
                            <i class="fas fa-broom"></i> 清理未使用
                        </button>
                    </div>
                </div>

                <!-- 统计信息 -->
                <div class="stats-grid">
                    <?php
                    $stats = [
                        'total' => $db->fetchOne("SELECT COUNT(*) as count FROM tags")['count'],
                        'used' => $db->fetchOne("SELECT COUNT(DISTINCT tag_id) as count FROM article_tags")['count'],
                        'unused' => $db->fetchOne("SELECT COUNT(*) as count FROM tags WHERE id NOT IN (SELECT DISTINCT tag_id FROM article_tags WHERE tag_id IS NOT NULL)")['count']
                    ];
                    ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                            <div class="stat-label">标签总数</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #27ae60;">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['used']); ?></div>
                            <div class="stat-label">已使用</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #95a5a6;">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['unused']); ?></div>
                            <div class="stat-label">未使用</div>
                        </div>
                    </div>
                </div>

                <!-- 搜索和筛选 -->
                <div class="filter-section">
                    <form method="GET" class="search-form">
                        <div class="search-input">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="搜索标签名称或描述..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 搜索
                            </button>
                            <?php if ($search): ?>
                                <a href="tags.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> 清除
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- 批量操作栏 -->
                <div class="batch-actions" style="display: none;">
                    <div class="batch-content">
                        <span>已选择 <strong class="selected-count">0</strong> 个标签</span>
                        <div class="batch-buttons">
                            <button onclick="batchDelete()" class="btn btn-sm btn-danger">批量删除</button>
                            <button onclick="clearSelection()" class="btn btn-sm btn-secondary">取消选择</button>
                        </div>
                    </div>
                </div>

                <!-- 标签列表 -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>标签列表</h3>
                        <div class="card-actions">
                            <span class="item-count">共找到 <?php echo $totalCount; ?> 个标签</span>
                        </div>
                    </div>
                    
                    <?php if (empty($tags)): ?>
                        <div class="empty-state">
                            <i class="fas fa-tags"></i>
                            <h3>暂无标签</h3>
                            <p>创建第一个标签来组织您的内容</p>
                            <a href="tags.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 创建标签
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="select-all" onchange="toggleSelectAll(this)">
                                        </th>
                                        <th>标签信息</th>
                                        <th width="120">颜色</th>
                                        <th width="100">URL别名</th>
                                        <th width="100">使用次数</th>
                                        <th width="140">创建时间</th>
                                        <th width="150">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tags as $tag): ?>
                                        <tr data-id="<?php echo $tag['id']; ?>">
                                            <td>
                                                <input type="checkbox" class="row-select" value="<?php echo $tag['id']; ?>" 
                                                       onchange="updateBatchActions()">
                                            </td>
                                            <td>
                                                <div class="tag-info">
                                                    <div class="tag-name">
                                                        <span class="tag-badge" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>">
                                                            <?php echo htmlspecialchars($tag['name']); ?>
                                                        </span>
                                                    </div>
                                                    <?php if ($tag['description']): ?>
                                                        <div class="tag-description">
                                                            <?php echo htmlspecialchars($tag['description']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="color-display">
                                                    <div class="color-preview" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>"></div>
                                                    <span class="color-code"><?php echo htmlspecialchars($tag['color']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="slug-code"><?php echo htmlspecialchars($tag['slug']); ?></code>
                                            </td>
                                            <td>
                                                <span class="usage-count">
                                                    <i class="fas fa-file-alt"></i>
                                                    <?php echo number_format($tag['article_count']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <span class="date-main">
                                                        <?php echo date('Y-m-d', strtotime($tag['created_at'])); ?>
                                                    </span>
                                                    <span class="date-time">
                                                        <?php echo date('H:i', strtotime($tag['created_at'])); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="category-actions">
                                                    <a href="tags.php?action=edit&id=<?php echo $tag['id']; ?>" 
                                                       class="btn-action btn-primary" title="编辑">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <button onclick="deleteTag(<?php echo $tag['id']; ?>)" 
                                                            class="btn-action btn-danger" title="删除">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- 分页 -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-wrapper">
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                                            <i class="fas fa-chevron-left"></i> 上一页
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                                            下一页 <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>

        <style>
        /* 标签管理样式 - 与分类管理保持一致 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .stat-content {
            flex: 1;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
            line-height: 1;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .search-form {
            display: flex;
            justify-content: center;
        }

        .search-input {
            display: flex;
            gap: 0.5rem;
            max-width: 600px;
            width: 100%;
        }

        .search-input input {
            flex: 1;
        }

        .batch-actions {
            background: #e74c3c;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .batch-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .batch-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            color: #2c3e50;
        }

        .item-count {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .tag-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .tag-badge {
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }

        .tag-description {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .color-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .color-preview {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .color-code {
            font-family: monospace;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .slug-code {
            background: #f8f9fa;
            color: #e83e8c;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .usage-count {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #6c757d;
        }

        .date-info {
            display: flex;
            flex-direction: column;
        }

        .date-main {
            font-weight: 500;
            color: #495057;
        }

        .date-time {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .category-actions {
            display: flex;
            gap: 0.25rem;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            color: #495057;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn-action.btn-primary:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .btn-action.btn-danger:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .pagination-wrapper {
            padding: 1.5rem;
            text-align: center;
            background: #f8f9fa;
            border-top: 1px solid #f0f0f0;
        }

        .pagination {
            display: inline-flex;
            gap: 0.25rem;
        }

        .page-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .page-btn:hover {
            background: #e9ecef;
            color: #495057;
            text-decoration: none;
        }

        .page-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header-left h1 {
            color: #2c3e50;
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }

        .header-left p {
            color: #6c757d;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 0.5rem;
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
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            text-decoration: none;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .search-input {
                flex-direction: column;
            }

            .batch-content {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        // 标签管理JavaScript功能

        // 选择所有复选框
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.row-select');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBatchActions();
        }

        // 更新批量操作栏
        function updateBatchActions() {
            const selected = document.querySelectorAll('.row-select:checked');
            const batchActions = document.querySelector('.batch-actions');
            const selectedCount = document.querySelector('.selected-count');
            
            if (selected.length > 0) {
                batchActions.style.display = 'block';
                selectedCount.textContent = selected.length;
            } else {
                batchActions.style.display = 'none';
            }
        }

        // 清除选择
        function clearSelection() {
            const checkboxes = document.querySelectorAll('.row-select, .select-all');
            checkboxes.forEach(cb => cb.checked = false);
            updateBatchActions();
        }

        // 删除单个标签
        function deleteTag(id) {
            if (confirm('确定要删除这个标签吗？删除后无法恢复。')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input name="action" value="delete">
                    <input name="id" value="${id}">
                `;
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 批量删除
        function batchDelete() {
            const selected = document.querySelectorAll('.row-select:checked');
            const ids = Array.from(selected).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('请选择要删除的标签');
                return;
            }
            
            if (confirm(`确定要删除选中的 ${ids.length} 个标签吗？删除后无法恢复。`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input name="action" value="batch_delete">
                    ${ids.map(id => `<input name="ids[]" value="${id}">`).join('')}
                `;
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 显示清理模态框
        function showCleanModal() {
            if (confirm('确定要清理所有未使用的标签吗？这将删除没有关联任何文章的标签。')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `<input name="action" value="clean_unused">`;
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 绑定行选择事件
            document.querySelectorAll('.row-select').forEach(checkbox => {
                checkbox.addEventListener('change', updateBatchActions);
            });
        });
        </script>

        <?php
        break;
}
?>