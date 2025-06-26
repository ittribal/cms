
// admin/category_list.php - 分类列表页面

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

$categories = getCategoriesTree();

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
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
            
            <?php if (empty($categories)):
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

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

/* 分类表单样式 */
.category-form {
    max-width: 800px;
    margin: 0 auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.color-picker-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.color-preview {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 2px solid #dee2e6;
    cursor: pointer;
}

.icon-picker {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
}

.icon-option {
    width: 40px;
    height: 40px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: white;
    transition: all 0.2s ease;
}

.icon-option:hover,
.icon-option.selected {
    border-color: #3498db;
    background: #e3f2fd;
    color: #3498db;
}

@media (max-width: 768px) {
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
    
    .form-row {
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
    
    confirmAction(`确定要${statusText}这个分类吗？`, () => {
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
    });
}

// 删除分类
function deleteCategory(id) {
    confirmAction('确定要删除这个分类吗？删除后无法恢复。', () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.innerHTML = `
            <input name="action" value="delete">
            <input name="id" value="${id}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    });
}

// 重建分类树
function showBuildTreeModal() {
    confirmAction('重建分类树将重新计算所有分类的层级关系，确定继续吗？', () => {
        showLoading('正在重建分类树...');
        
        fetch('rebuild_category_tree.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showToast('重建失败，请重试', 'error');
        });
    });
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 默认收起二级以下分类
    collapseAll();
    
    // 拖拽排序功能
    initSortable();
});

// 初始化拖拽排序
function initSortable() {
    const categoryTree = document.getElementById('categoryTree');
    if (!categoryTree) return;
    
    // 简单的拖拽排序实现
    let draggedElement = null;
    
    categoryTree.addEventListener('dragstart', function(e) {
        if (e.target.classList.contains('category-item')) {
            draggedElement = e.target;
            e.target.style.opacity = '0.5';
        }
    });
    
    categoryTree.addEventListener('dragend', function(e) {
        if (e.target.classList.contains('category-item')) {
            e.target.style.opacity = '';
            draggedElement = null;
        }
    });
    
    categoryTree.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    categoryTree.addEventListener('drop', function(e) {
        e.preventDefault();
        
        if (draggedElement && e.target.closest('.category-item')) {
            const targetElement = e.target.closest('.category-item');
            
            if (draggedElement !== targetElement) {
                const rect = targetElement.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                
                if (e.clientY < midpoint) {
                    categoryTree.insertBefore(draggedElement, targetElement);
                } else {
                    categoryTree.insertBefore(draggedElement, targetElement.nextSibling);
                }
                
                // 保存新的排序
                saveCategoryOrder();
            }
        }
    });
    
    // 为所有分类项添加draggable属性
    document.querySelectorAll('.category-item').forEach(item => {
        item.draggable = true;
    });
}

// 保存分类排序
function saveCategoryOrder() {
    const categoryItems = document.querySelectorAll('.category-item');
    const orderData = [];
    
    categoryItems.forEach((item, index) => {
        orderData.push({
            id: item.dataset.id,
            sort_order: index + 1
        });
    });
    
    fetch('save_category_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('排序保存成功', 'success');
        } else {
            showToast('排序保存失败', 'error');
        }
    })
    .catch(error => {
        showToast('排序保存失败', 'error');
    });
}
</script>