<?php
// admin/article_list.php - 文章列表页面

// 获取筛选参数
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = ["a.status != 'trash'"];
$params = [];

if ($status && $status !== 'all') {
    $where[] = "a.status = ?";
    $params[] = $status;
}

if ($category) {
    $where[] = "a.category_id = ?";
    $params[] = $category;
}

if ($author) {
    $where[] = "a.author_id = ?";
    $params[] = $author;
}

if ($search) {
    $where[] = "(a.title LIKE ? OR a.content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 获取文章列表
$sql = "SELECT a.*, c.name as category_name, u.username as author_name,
               (SELECT COUNT(*) FROM comments WHERE article_id = a.id AND status = 'approved') as comment_count
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        LEFT JOIN users u ON a.author_id = u.id 
        WHERE {$whereClause}
        ORDER BY a.created_at DESC 
        LIMIT ? OFFSET ?";

$articles = $db->fetchAll($sql, array_merge($params, [$limit, $offset]));

// 获取总数
$countSql = "SELECT COUNT(*) as total FROM articles a WHERE {$whereClause}";
$totalCount = $db->fetchOne($countSql, $params)['total'];
$totalPages = ceil($totalCount / $limit);

// 获取分类列表
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

// 获取作者列表
$authors = $db->fetchAll("SELECT id, username, real_name FROM users WHERE status = 'active' ORDER BY username");

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-file-alt"></i> 文章管理</h1>
                <p>管理网站文章内容，包括发布、编辑、删除等操作</p>
            </div>
            <div class="header-actions">
                <?php if ($auth->hasPermission('article.create')): ?>
                    <a href="articles.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新建文章
                    </a>
                <?php endif; ?>
                <button onclick="showImportModal()" class="btn btn-info">
                    <i class="fas fa-upload"></i> 导入文章
                </button>
                <button onclick="exportArticles()" class="btn btn-success">
                    <i class="fas fa-download"></i> 导出文章
                </button>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="stats-grid">
            <?php
            $stats = [
                'total' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status != 'trash'")['count'],
                'published' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'],
                'draft' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'draft'")['count'],
                'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'pending'")['count']
            ];
            ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: #3498db;">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">文章总数</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #27ae60;">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['published']); ?></div>
                    <div class="stat-label">已发布</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f39c12;">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['draft']); ?></div>
                    <div class="stat-label">草稿</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e74c3c;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
                    <div class="stat-label">待审核</div>
                </div>
            </div>
        </div>

        <!-- 筛选和搜索 -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <select name="status" class="form-control">
                        <option value="">所有状态</option>
                        <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已发布</option>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                        <option value="private" <?php echo $status === 'private' ? 'selected' : ''; ?>>私密</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="category" class="form-control">
                        <option value="">所有分类</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="author" class="form-control">
                        <option value="">所有作者</option>
                        <?php foreach ($authors as $authorItem): ?>
                            <option value="<?php echo $authorItem['id']; ?>" 
                                    <?php echo $author == $authorItem['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($authorItem['real_name'] ?: $authorItem['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="search-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="搜索文章标题或内容..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="articles.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 清除
                    </a>
                </div>
            </form>
        </div>

        <!-- 批量操作栏 -->
        <div class="batch-actions" style="display: none;">
            <div class="batch-content">
                <span>已选择 <strong class="selected-count">0</strong> 篇文章</span>
                <div class="batch-buttons">
                    <button onclick="batchAction('published')" class="btn btn-sm btn-success">批量发布</button>
                    <button onclick="batchAction('draft')" class="btn btn-sm btn-warning">设为草稿</button>
                    <button onclick="batchAction('delete')" class="btn btn-sm btn-danger">批量删除</button>
                    <button onclick="clearSelection()" class="btn btn-sm btn-secondary">取消选择</button>
                </div>
            </div>
        </div>

        <!-- 文章列表 -->
        <div class="content-card">
            <div class="card-header">
                <h3>文章列表</h3>
                <div class="card-actions">
                    <button onclick="toggleListView()" class="btn btn-sm btn-outline" title="切换视图">
                        <i class="fas fa-th-list"></i>
                    </button>
                    <button onclick="refreshList()" class="btn btn-sm btn-outline" title="刷新列表">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table" id="articlesTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="select-all" onchange="toggleSelectAll(this)">
                            </th>
                            <th width="80">封面</th>
                            <th>标题</th>
                            <th width="100">分类</th>
                            <th width="100">作者</th>
                            <th width="80">状态</th>
                            <th width="80">评论</th>
                            <th width="80">浏览</th>
                            <th width="120">创建时间</th>
                            <th width="150">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles)): ?>
                            <tr>
                                <td colspan="10" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-file-alt"></i>
                                        <p>暂无文章数据</p>
                                        <?php if ($auth->hasPermission('article.create')): ?>
                                            <a href="articles.php?action=add" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> 创建第一篇文章
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($articles as $article): ?>
                                <tr data-id="<?php echo $article['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="row-select" value="<?php echo $article['id']; ?>" 
                                               onchange="updateBatchActions()">
                                    </td>
                                    <td>
                                        <?php if ($article['featured_image']): ?>
                                            <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                                 alt="封面" class="article-thumb">
                                        <?php else: ?>
                                            <div class="article-thumb-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="article-title">
                                            <a href="articles.php?action=view&id=<?php echo $article['id']; ?>" 
                                               class="title-link">
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </a>
                                            <?php if ($article['is_featured']): ?>
                                                <span class="featured-badge" title="推荐文章">
                                                    <i class="fas fa-star"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="article-meta">
                                            <span class="article-id">ID: <?php echo $article['id']; ?></span>
                                            <span class="article-slug">别名: <?php echo htmlspecialchars($article['slug']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($article['category_name']): ?>
                                            <span class="category-tag">
                                                <?php echo htmlspecialchars($article['category_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">未分类</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="author-info">
                                            <span class="author-name">
                                                <?php echo htmlspecialchars($article['author_name']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $article['status']; ?>" 
                                              onclick="toggleStatus(<?php echo $article['id']; ?>, '<?php echo $article['status']; ?>')">
                                            <?php
                                            $statusLabels = [
                                                'published' => '已发布',
                                                'draft' => '草稿',
                                                'pending' => '待审核',
                                                'private' => '私密'
                                            ];
                                            echo $statusLabels[$article['status']] ?? $article['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="comment-count">
                                            <i class="fas fa-comments"></i>
                                            <?php echo $article['comment_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="view-count">
                                            <i class="fas fa-eye"></i>
                                            <?php echo number_format($article['views']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <span class="date-main">
                                                <?php echo date('Y-m-d', strtotime($article['created_at'])); ?>
                                            </span>
                                            <span class="date-time">
                                                <?php echo date('H:i', strtotime($article['created_at'])); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="articles.php?action=view&id=<?php echo $article['id']; ?>" 
                                               class="btn-action btn-info" title="查看">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($auth->hasPermission('article.edit') || 
                                                     ($auth->hasPermission('article.edit_own') && 
                                                      $article['author_id'] == $currentUser['id'])): ?>
                                                <a href="articles.php?action=edit&id=<?php echo $article['id']; ?>" 
                                                   class="btn-action btn-primary" title="编辑">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="../public/article.php?slug=<?php echo $article['slug']; ?>" 
                                               target="_blank" class="btn-action btn-success" title="预览">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            
                                            <?php if ($auth->hasPermission('article.delete') || 
                                                     ($auth->hasPermission('article.delete_own') && 
                                                      $article['author_id'] == $currentUser['id'])): ?>
                                                <button onclick="deleteArticle(<?php echo $article['id']; ?>)" 
                                                        class="btn-action btn-danger" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <?php echo generatePagination($page, $totalPages, $_GET); ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<!-- 导入文章模态框 -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-upload"></i> 导入文章</h3>
            <button type="button" class="close" onclick="closeModal('importModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="importForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="importFile">选择文件</label>
                    <input type="file" id="importFile" name="import_file" class="form-control" 
                           accept=".csv,.xlsx,.json" required>
                    <small class="form-text">支持CSV、Excel、JSON格式文件</small>
                </div>
                
                <div class="form-group">
                    <label>导入选项</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="skip_existing" checked>
                            跳过已存在的文章（根据标题判断）
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="auto_publish">
                            自动发布导入的文章
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="create_categories">
                            自动创建不存在的分类
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> 开始导入
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('importModal')">
                        取消
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* 文章管理样式 */
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

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) 2fr;
    gap: 1rem;
    align-items: end;
}

.search-group {
    display: flex;
    gap: 0.5rem;
}

.search-group input {
    flex: 1;
}

.batch-actions {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
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

.batch-buttons .btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
}

.batch-buttons .btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.article-thumb {
    width: 60px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.article-thumb-placeholder {
    width: 60px;
    height: 40px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.article-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.title-link {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 500;
}

.title-link:hover {
    color: #3498db;
}

.featured-badge {
    color: #f39c12;
    font-size: 0.8rem;
}

.article-meta {
    font-size: 0.8rem;
    color: #6c757d;
}

.article-meta span {
    margin-right: 1rem;
}

.category-tag {
    background: #e9ecef;
    color: #495057;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.8rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-published {
    background: #d4edda;
    color: #155724;
}

.status-draft {
    background: #fff3cd;
    color: #856404;
}

.status-pending {
    background: #cce5ff;
    color: #004085;
}

.status-private {
    background: #f8d7da;
    color: #721c24;
}

.status-badge:hover {
    transform: scale(1.05);
}

.comment-count, .view-count {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.9rem;
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

.action-buttons {
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
    font-size: 0.9rem;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn-action.btn-info:hover {
    background: #17a2b8;
    color: white;
    border-color: #17a2b8;
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

.btn-action.btn-danger:hover {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    cursor: pointer;
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

@media (max-width: 768px) {
    .filter-form {
        grid-template-columns: 1fr;
    }
    
    .batch-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// 文章管理JavaScript功能

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

// 批量操作
function batchAction(action) {
    const selected = document.querySelectorAll('.row-select:checked');
    const ids = Array.from(selected).map(cb => cb.value);
    
    if (ids.length === 0) {
        showToast('请选择要操作的文章', 'warning');
        return;
    }
    
    let message = '';
    let confirmText = '';
    
    switch (action) {
        case 'published':
            message = `确定要发布选中的 ${ids.length} 篇文章吗？`;
            confirmText = '发布';
            break;
        case 'draft':
            message = `确定要将选中的 ${ids.length} 篇文章设为草稿吗？`;
            confirmText = '设为草稿';
            break;
        case 'delete':
            message = `确定要删除选中的 ${ids.length} 篇文章吗？删除后将移至回收站。`;
            confirmText = '删除';
            break;
    }
    
    confirmAction(message, () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        if (action === 'delete') {
            form.innerHTML = `
                <input name="action" value="batch_delete">
                ${ids.map(id => `<input name="ids[]" value="${id}">`).join('')}
            `;
        } else {
            form.innerHTML = `
                <input name="action" value="batch_status">
                <input name="status" value="${action}">
                ${ids.map(id => `<input name="ids[]" value="${id}">`).join('')}
            `;
        }
        
        document.body.appendChild(form);
        form.submit();
    });
}

// 删除单个文章
function deleteArticle(id) {
    confirmAction('确定要删除这篇文章吗？删除后将移至回收站。', () => {
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

// 切换文章状态
function toggleStatus(id, currentStatus) {
    const statusOptions = {
        'draft': 'published',
        'published': 'draft',
        'pending': 'published',
        'private': 'published'
    };
    
    const newStatus = statusOptions[currentStatus] || 'draft';
    const statusLabels = {
        'published': '发布',
        'draft': '草稿',
        'pending': '待审核',
        'private': '私密'
    };
    
    confirmAction(`确定要将文章状态改为"${statusLabels[newStatus]}"吗？`, () => {
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

// 显示导入模态框
function showImportModal() {
    document.getElementById('importModal').style.display = 'block';
}

// 导出文章
function exportArticles() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_articles.php';
    form.style.display = 'none';
    
    // 添加当前筛选条件
    const searchParams = new URLSearchParams(window.location.search);
    for (const [key, value] of searchParams) {
        if (key !== 'page') {
            const input = document.createElement('input');
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    }
    
    document.body.appendChild(form);
    form.submit();
    form.remove();
    
    showToast('正在准备导出文件...', 'info');
}

// 切换列表视图
function toggleListView() {
    const table = document.getElementById('articlesTable');
    table.classList.toggle('compact-view');
    
    const icon = event.target.closest('button').querySelector('i');
    if (table.classList.contains('compact-view')) {
        icon.className = 'fas fa-th';
    } else {
        icon.className = 'fas fa-th-list';
    }
}

// 刷新列表
function refreshList() {
    window.location.reload();
}

// 处理导入表单
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const file = formData.get('import_file');
            
            if (!file || file.size === 0) {
                showToast('请选择要导入的文件', 'error');
                return;
            }
            
            // 检查文件类型
            const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/json'];
            
            if (!allowedTypes.includes(file.type)) {
                showToast('不支持的文件格式，请选择CSV、Excel或JSON文件', 'error');
                return;
            }
            
            // 显示上传进度
            showLoading('正在导入文章，请稍候...');
            
            fetch('import_articles.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                closeModal('importModal');
                
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showToast('导入失败，请重试', 'error');
                console.error('Import error:', error);
            });
        });
    }
    
    // 绑定行选择事件
    document.querySelectorAll('.row-select').forEach(checkbox => {
        checkbox.addEventListener('change', updateBatchActions);
    });
    
    // 表格行双击编辑
    document.querySelectorAll('#articlesTable tbody tr').forEach(row => {
        row.addEventListener('dblclick', function() {
            const editLink = this.querySelector('a[href*="action=edit"]');
            if (editLink) {
                window.location.href = editLink.href;
            }
        });
    });
});

// 分页函数
function generatePagination(currentPage, totalPages, params) {
    let html = '<div class="pagination">';
    
    // 构建URL参数
    const urlParams = new URLSearchParams(params);
    
    // 上一页
    if (currentPage > 1) {
        urlParams.set('page', currentPage - 1);
        html += `<a href="?${urlParams.toString()}" class="page-btn">
                    <i class="fas fa-chevron-left"></i> 上一页
                 </a>`;
    }
    
    // 页码
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        urlParams.set('page', i);
        const activeClass = i === currentPage ? 'active' : '';
        html += `<a href="?${urlParams.toString()}" class="page-btn ${activeClass}">${i}</a>`;
    }
    
    // 下一页
    if (currentPage < totalPages) {
        urlParams.set('page', currentPage + 1);
        html += `<a href="?${urlParams.toString()}" class="page-btn">
                    下一页 <i class="fas fa-chevron-right"></i>
                 </a>`;
    }
    
    html += '</div>';
    return html;
}
</script>