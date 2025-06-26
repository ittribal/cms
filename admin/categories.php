<?php
// admin/categories.php - 分类管理
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = '分类管理';
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// 处理操作
if ($_POST) {
    $postAction = $_POST['action'] ?? '';
    
    switch ($postAction) {
        case 'add':
        case 'edit':
            $result = handleCategoryForm($_POST, $postAction);
            if ($result['success']) {
                $message = $result['message'];
                if ($postAction === 'add') {
                    header('Location: categories.php?message=' . urlencode($message));
                    exit;
                }
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'delete':
            $result = deleteCategory($_POST['id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'toggle_status':
            $result = toggleCategoryStatus($_POST['id'], $_POST['status']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// 处理分类表单
function handleCategoryForm($data, $action) {
    global $db, $auth;
    
    try {
        if (empty($data['name'])) {
            return ['success' => false, 'message' => '分类名称不能为空'];
        }
        
        $slug = !empty($data['slug']) ? $data['slug'] : generateSlug($data['name']);
        
        // 检查名称和别名重复
        $existingSql = "SELECT id FROM categories WHERE (name = ? OR slug = ?) AND id != ?";
        $existingId = $action === 'edit' ? $data['id'] : 0;
        $existing = $db->fetchOne($existingSql, [$data['name'], $slug, $existingId]);
        
        if ($existing) {
            return ['success' => false, 'message' => '分类名称或别名已存在'];
        }
        
        $categoryData = [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'parent_id' => $data['parent_id'] ?: null,
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => $data['status'] ?? 'active',
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'color' => $data['color'] ?? '#3498db',
            'icon' => $data['icon'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($action === 'add') {
            $categoryData['created_at'] = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO categories (" . implode(', ', array_keys($categoryData)) . ") 
                    VALUES (" . str_repeat('?,', count($categoryData) - 1) . "?)";
            $result = $db->execute($sql, array_values($categoryData));
            
            if ($result) {
                $categoryId = $db->getLastInsertId();
                $auth->logAction('添加分类', '分类ID: ' . $categoryId);
                return ['success' => true, 'message' => '分类添加成功'];
            }
        } else {
            $categoryId = $data['id'];
            $setClause = implode(' = ?, ', array_keys($categoryData)) . ' = ?';
            $sql = "UPDATE categories SET {$setClause} WHERE id = ?";
            $params = array_merge(array_values($categoryData), [$categoryId]);
            
            $result = $db->execute($sql, $params);
            
            if ($result) {
                $auth->logAction('编辑分类', '分类ID: ' . $categoryId);
                return ['success' => true, 'message' => '分类更新成功'];
            }
        }
        
        return ['success' => false, 'message' => '操作失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '操作失败：' . $e->getMessage()];
    }
}

// 删除分类
function deleteCategory($id) {
    global $db, $auth;
    
    try {
        // 检查是否有文章使用该分类
        $articleCount = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE category_id = ?", [$id])['count'];
        
        if ($articleCount > 0) {
            return ['success' => false, 'message' => '该分类下还有文章，无法删除'];
        }
        
        // 检查是否有子分类
        $childCount = $db->fetchOne("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?", [$id])['count'];
        
        if ($childCount > 0) {
            return ['success' => false, 'message' => '该分类下还有子分类，无法删除'];
        }
        
        $result = $db->execute("DELETE FROM categories WHERE id = ?", [$id]);
        
        if ($result) {
            $auth->logAction('删除分类', '分类ID: ' . $id);
            return ['success' => true, 'message' => '分类删除成功'];
        }
        
        return ['success' => false, 'message' => '删除失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '删除失败：' . $e->getMessage()];
    }
}

// 切换分类状态
function toggleCategoryStatus($id, $status) {
    global $db, $auth;
    
    try {
        $validStatus = ['active', 'inactive'];
        if (!in_array($status, $validStatus)) {
            return ['success' => false, 'message' => '无效的状态'];
        }
        
        $result = $db->execute("UPDATE categories SET status = ?, updated_at = NOW() WHERE id = ?", 
                              [$status, $id]);
        
        if ($result) {
            $auth->logAction('修改分类状态', "分类ID: {$id}, 状态: {$status}");
            return ['success' => true, 'message' => '状态更新成功'];
        }
        
        return ['success' => false, 'message' => '状态更新失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '状态更新失败：' . $e->getMessage()];
    }
}

// 生成URL别名
function generateSlug($title) {
    // 简单的中文转拼音处理（实际项目中建议使用专门的拼音库）
    $slug = preg_replace('/[^a-zA-Z0-9\u4e00-\u9fa5]+/', '-', $title);
    $slug = trim($slug, '-');
    $slug = strtolower($slug);
    return $slug ?: 'category-' . time();
}

// 获取分类树形结构
function getCategoriesTree($parentId = null, $level = 0) {
    global $db;
    
    $sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM articles WHERE category_id = c.id AND status = 'published') as article_count,
                   (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count
            FROM categories c 
            WHERE " . ($parentId ? "parent_id = ?" : "parent_id IS NULL") . "
            ORDER BY sort_order ASC, name ASC";
    
    $params = $parentId ? [$parentId] : [];
    $categories = $db->fetchAll($sql, $params);
    
    $result = [];
    foreach ($categories as $category) {
        $category['level'] = $level;
        $result[] = $category;
        
        // 递归获取子分类
        $children = getCategoriesTree($category['id'], $level + 1);
        $result = array_merge($result, $children);
    }
    
    return $result;
}

// 根据操作显示页面
switch ($action) {
    case 'add':
    case 'edit':
        include 'category_form.php';
        break;
    default:
        // 显示分类列表
        $categories = getCategoriesTree();
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
                        <h1><i class="fas fa-folder"></i> 分类管理</h1>
                        <p>管理文章分类，支持多级分类和拖拽排序</p>
                    </div>
                    <div class="header-actions">
                        <a href="categories.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 新建分类
                        </a>
                        <button onclick="showBuildTreeModal()" class="btn btn-info">
                            <i class="fas fa-sitemap"></i> 重建分类树
                        </button>
                    </div>
                </div>

                <!-- 统计信息 -->
                <div class="stats-grid">
                    <?php
                    $stats = [
                        'total' => $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'],
                        'active' => $db->fetchOne("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count'],
                        'with_articles' => $db->fetchOne("SELECT COUNT(DISTINCT category_id) as count FROM articles WHERE category_id IS NOT NULL")['count']
                    ];
                    ?>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #9b59b6;">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                            <div class="stat-label">分类总数</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #27ae60;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                            <div class="stat-label">启用分类</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['with_articles']); ?></div>
                            <div class="stat-label">有文章的分类</div>
                        </div>
                    </div>
                </div>

                <!-- 分类列表 -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>分类列表</h3>
                        <div class="card-actions">
                            <button onclick="expandAll()" class="btn btn-sm btn-outline">
                                <i class="fas fa-expand-alt"></i> 展开全部
                            </button>
                            <button onclick="collapseAll()" class="btn btn-sm btn-outline">
                                <i class="fas fa-compress-alt"></i> 收起全部
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($categories)): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-plus"></i>
                            <h3>暂无分类</h3>
                            <p>创建第一个分类来组织您的内容</p>
                            <a href="categories.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 创建分类
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="category-tree" id="categoryTree">
                            <?php foreach ($categories as $category): ?>
                                <div class="category-item" data-id="<?php echo $category['id']; ?>" 
                                     data-level="<?php echo $category['level']; ?>">
                                    <div class="category-content" style="padding-left: <?php echo $category['level'] * 30; ?>px;">
                                        <!-- 展开/收起按钮 -->
                                        <?php if ($category['child_count'] > 0): ?>
                                            <button class="expand-btn" onclick="toggleCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="expand-placeholder"></span>
                                        <?php endif; ?>
                                        
                                        <!-- 分类图标 -->
                                        <div class="category-icon" style="background-color: <?php echo $category['color']; ?>">
                                            <?php if ($category['icon']): ?>
                                                <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                            <?php else: ?>
                                                <i class="fas fa-folder"></i>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- 分类信息 -->
                                        <div class="category-info">
                                            <div class="category-name">
                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                <span class="category-slug">/<?php echo htmlspecialchars($category['slug']); ?></span>
                                            </div>
                                            <div class="category-meta">
                                                <span class="article-count">
                                                    <i class="fas fa-file-alt"></i> 
                                                    <?php echo $category['article_count']; ?> 篇文章
                                                </span>
                                                <?php if ($category['description']): ?>
                                                    <span class="category-desc">
                                                        <?php echo htmlspecialchars(mb_substr($category['description'], 0, 50)); ?>
                                                        <?php if (mb_strlen($category['description']) > 50): ?>...<?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- 状态标识 -->
                                        <div class="category-status">
                                            <span class="status-badge status-<?php echo $category['status']; ?>" 
                                                  onclick="toggleCategoryStatus(<?php echo $category['id']; ?>, '<?php echo $category['status']; ?>')">
                                                <?php echo $category['status'] === 'active' ? '启用' : '禁用'; ?>
                                            </span>
                                        </div>
                                        
                                        <!-- 排序号 -->
                                        <div class="sort-order">
                                            <span class="sort-number"><?php echo $category['sort_order']; ?></span>
                                        </div>
                                        
                                        <!-- 操作按钮 -->
                                        <div class="category-actions">
                                            <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" 
                                               class="btn-action btn-primary" title="编辑">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <a href="categories.php?action=add&parent_id=<?php echo $category['id']; ?>" 
                                               class="btn-action btn-success" title="添加子分类">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                            
                                            <a href="../public/category.php?slug=<?php echo $category['slug']; ?>" 
                                               target="_blank" class="btn-action btn-info" title="查看">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            
                                            <?php if ($category['article_count'] == 0 && $category['child_count'] == 0): ?>
                                                <button onclick="deleteCategory(<?php echo $category['id']; ?>)" 
                                                        class="btn-action btn-danger" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>

        <style>
        /* 分类管理样式 */
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

        .card-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #dee2e6;
            color: #6c757d;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-outline:hover {
            background: #f8f9fa;
            border-color: #6c757d;
        }

        .category-tree {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .category-item {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-item:hover {
            background: #f8f9fa;
        }

        .category-content {
            display: flex;
            align-items: center;
            padding: 1rem;
            gap: 1rem;
        }

        .expand-btn {
            width: 24px;
            height: 24px;
            border: none;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .expand-btn:hover {
            background: #e9ecef;
        }

        .expand-btn.expanded i {
            transform: rotate(90deg);
        }

        .expand-placeholder {
            width: 24px;
            height: 24px;
        }

        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .category-info {
            flex: 1;
            min-width: 0;
        }

        .category-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .category-name strong {
            color: #2c3e50;
            font-size: 1rem;
        }

        .category-slug {
            color: #6c757d;
            font-size: 0.85rem;
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }

        .category-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .article-count {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .category-desc {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .category-status {
            flex-shrink: 0;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .sort-order {
            width: 60px;
            text-align: center;
            flex-shrink: 0;
        }

        .sort-number {
            background: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .category-actions {
            display: flex;
            gap: 0.25rem;
            flex-shrink: 0;
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

        .btn-action.btn-success:hover {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .btn-action.btn-info:hover {
            background: #17a2b8;
            color: white;
            border-color: #17a2b8;
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

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
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

            .category-content {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .category-info {
                order: 1;
                flex: 1 1 100%;
            }
            
            .category-status,
            .sort-order,
            .category-actions {
                order: 2;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        // 分类管理JavaScript

        // 展开/收起分类
        function toggleCategory(categoryId) {
            const btn = event.target.closest('.expand-btn');
            const isExpanded = btn.classList.contains('expanded');
            
            btn.classList.toggle('expanded');
            
            // 显示/隐藏子分类
            const categoryItems = document.querySelectorAll('.category-item');
            let found = false;
            let currentLevel = 0;
            
            categoryItems.forEach(item => {
                if (item.dataset.id == categoryId) {
                    found = true;
                    currentLevel = parseInt(item.dataset.level);
                    return;
                }
                
                if (found) {
                    const itemLevel = parseInt(item.dataset.level);
                    
                    if (itemLevel <= currentLevel) {
                        found = false;
                        return;
                    }
                    
                    if (itemLevel === currentLevel + 1) {
                        item.style.display = isExpanded ? 'none' : 'block';
                    }
                }
            });
        }

        // 展开所有分类
        function expandAll() {
            document.querySelectorAll('.category-item').forEach(item => {
                item.style.display = 'block';
            });
            
            document.querySelectorAll('.expand-btn').forEach(btn => {
                btn.classList.add('expanded');
            });
        }

        // 收起所有分类
        function collapseAll() {
            document.querySelectorAll('.category-item').forEach(item => {
                const level = parseInt(item.dataset.level);
                if (level > 0) {
                    item.style.display = 'none';
                }
            });
            
            document.querySelectorAll('.expand-btn').forEach(btn => {
                btn.classList.remove('expanded');
            });
        }

        // 切换分类状态
        function toggleCategoryStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const statusText = newStatus === 'active' ? '启用' : '禁用';
            
            if (confirm(`确定要${statusText}这个分类吗？`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input name="action" value="toggle_status">
                    <input name="id" value="${id}">
                    <input name="status" value="${newStatus}">
                `;
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 删除分类
        function deleteCategory(id) {
            if (confirm('确定要删除这个分类吗？删除后无法恢复。')) {
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

        // 重建分类树
        function showBuildTreeModal() {
            if (confirm('重建分类树将重新计算所有分类的层级关系，确定继续吗？')) {
                alert('重建分类树功能开发中...');
                // 这里可以实现重建分类树的逻辑
            }
        }

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 默认收起二级以下分类
            collapseAll();
        });
        </script>

        <?php
        break;
}
?>