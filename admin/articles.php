<?php
// admin/articles.php - 文章管理主页面（修复版本）
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录状态
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = '文章管理';
$currentUser = $auth->getCurrentUser();
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// 处理POST操作
if ($_POST) {
    $postAction = $_POST['action'] ?? '';
    
    switch ($postAction) {
        case 'delete':
            $result = deleteArticle($_POST['id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'batch_delete':
            $result = batchDeleteArticles($_POST['ids']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'toggle_status':
            $result = toggleArticleStatus($_POST['id'], $_POST['status']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'batch_status':
            $result = batchUpdateStatus($_POST['ids'], $_POST['status']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// 删除文章
function deleteArticle($id) {
    global $db, $auth;
    
    try {
        if (!$auth->hasPermission('article.delete')) {
            return ['success' => false, 'message' => '没有删除权限'];
        }
        
        // 软删除（移到回收站）
        $result = $db->execute("UPDATE articles SET status = 'archived', updated_at = NOW() WHERE id = ?", [$id]);
        
        if ($result) {
            $auth->logAction('删除文章', '文章ID: ' . $id);
            return ['success' => true, 'message' => '文章已移至回收站'];
        }
        
        return ['success' => false, 'message' => '删除失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '删除失败：' . $e->getMessage()];
    }
}

// 批量删除文章
function batchDeleteArticles($ids) {
    global $db, $auth;
    
    try {
        if (empty($ids) || !is_array($ids)) {
            return ['success' => false, 'message' => '请选择要删除的文章'];
        }
        
        if (!$auth->hasPermission('article.delete')) {
            return ['success' => false, 'message' => '没有删除权限'];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE articles SET status = 'archived', updated_at = NOW() WHERE id IN ({$placeholders})";
        
        $result = $db->execute($sql, $ids);
        
        if ($result) {
            $auth->logAction('批量删除文章', '文章数量: ' . count($ids));
            return ['success' => true, 'message' => '批量删除成功，共删除 ' . count($ids) . ' 篇文章'];
        }
        
        return ['success' => false, 'message' => '批量删除失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '批量删除失败：' . $e->getMessage()];
    }
}

// 切换文章状态
function toggleArticleStatus($id, $status) {
    global $db, $auth;
    
    try {
        $validStatus = ['draft', 'published', 'private'];
        if (!in_array($status, $validStatus)) {
            return ['success' => false, 'message' => '无效的状态'];
        }
        
        if (!$auth->hasPermission('article.edit')) {
            return ['success' => false, 'message' => '没有编辑权限'];
        }
        
        $updateData = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        
        // 如果是发布状态，设置发布时间
        if ($status === 'published') {
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }
        
        $setClause = implode(' = ?, ', array_keys($updateData)) . ' = ?';
        $result = $db->execute("UPDATE articles SET {$setClause} WHERE id = ?", 
                              array_merge(array_values($updateData), [$id]));
        
        if ($result) {
            $auth->logAction('修改文章状态', "文章ID: {$id}, 状态: {$status}");
            return ['success' => true, 'message' => '状态更新成功'];
        }
        
        return ['success' => false, 'message' => '状态更新失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '状态更新失败：' . $e->getMessage()];
    }
}

// 批量更新状态
function batchUpdateStatus($ids, $status) {
    global $db, $auth;
    
    try {
        if (empty($ids) || !is_array($ids)) {
            return ['success' => false, 'message' => '请选择要操作的文章'];
        }
        
        $validStatus = ['draft', 'published', 'private'];
        if (!in_array($status, $validStatus)) {
            return ['success' => false, 'message' => '无效的状态'];
        }
        
        if (!$auth->hasPermission('article.edit')) {
            return ['success' => false, 'message' => '没有编辑权限'];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $params = [$status, date('Y-m-d H:i:s')];
        
        $sql = "UPDATE articles SET status = ?, updated_at = ?";
        
        // 如果是发布状态，更新发布时间
        if ($status === 'published') {
            $sql .= ", published_at = ?";
            $params[] = date('Y-m-d H:i:s');
        }
        
        $sql .= " WHERE id IN ({$placeholders})";
        $params = array_merge($params, $ids);
        
        $result = $db->execute($sql, $params);
        
        if ($result) {
            $statusLabels = [
                'published' => '发布',
                'draft' => '草稿',
                'private' => '私密'
            ];
            
            $auth->logAction('批量修改文章状态', "状态: {$status}, 数量: " . count($ids));
            return ['success' => true, 'message' => '批量' . $statusLabels[$status] . '成功，共操作 ' . count($ids) . ' 篇文章'];
        }
        
        return ['success' => false, 'message' => '批量操作失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '批量操作失败：' . $e->getMessage()];
    }
}

// 获取筛选参数
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = ["a.status != 'archived'"];
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
$sql = "SELECT a.*, c.name as category_name, u.username as author_name
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
$authors = $db->fetchAll("SELECT id, username FROM users WHERE status = 'active' ORDER BY username");

include '../templates/admin_header.php';
?>

<div class="admin-container">
    <main class="main-content">
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
            <div class="header-left">
                <h1><i class="fas fa-file-alt"></i> 文章管理</h1>
                <p>管理网站文章内容，包括发布、编辑、删除等操作</p>
            </div>
            <div class="header-actions">
                <?php if ($auth->hasPermission('article.create')): ?>
                    <a href="article_add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新建文章
                    </a>
                <?php endif; ?>
                <a href="article_import.php" class="btn btn-info">
                    <i class="fas fa-upload"></i> 批量导入
                </a>
                <a href="article_export.php" class="btn btn-success">
                    <i class="fas fa-download"></i> 导出数据
                </a>
            </div>
        </div>

        <!-- 统计信息 -->
        <div class="stats-grid">
            <?php
            $stats = [
                'total' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status != 'archived'")['count'],
                'published' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'],
                'draft' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'draft'")['count'],
                'private' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'private'")['count']
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
                    <i class="fas fa-lock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['private']); ?></div>
                    <div class="stat-label">私密</div>
                </div>
            </div>
        </div>

        <!-- 搜索和筛选 -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <select name="status" class="form-control">
                        <option value="">所有状态</option>
                        <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已发布</option>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
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
                                <?php echo htmlspecialchars($authorItem['username']); ?>
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
                    <button onclick="batchAction('private')" class="btn btn-sm btn-info">设为私密</button>
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
                    <span class="item-count">共找到 <?php echo number_format($totalCount); ?> 篇文章</span>
                    <button onclick="toggleListView()" class="btn btn-sm btn-outline" title="切换视图">
                        <i class="fas fa-th-list"></i>
                    </button>
                    <button onclick="refreshList()" class="btn btn-sm btn-outline" title="刷新列表">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>暂无文章</h3>
                    <p>创建第一篇文章来开始您的内容创作</p>
                    <?php if ($auth->hasPermission('article.create')): ?>
                        <a href="article_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 创建文章
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table" id="articlesTable">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" class="select-all" onchange="toggleSelectAll(this)">
                                </th>
                                <th>标题</th>
                                <th width="100">分类</th>
                                <th width="80">作者</th>
                                <th width="80">状态</th>
                                <th width="80">浏览</th>
                                <th width="120">创建时间</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                                <tr data-id="<?php echo $article['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="row-select" value="<?php echo $article['id']; ?>" 
                                               onchange="updateBatchActions()">
                                    </td>
                                    <td>
                                        <div class="article-title">
                                            <a href="article_edit.php?id=<?php echo $article['id']; ?>" 
                                               class="title-link">
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </a>
                                            <?php if ($article['featured_image']): ?>
                                                <span class="has-image" title="有特色图片">
                                                    <i class="fas fa-image"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="article-meta">
                                            <span class="article-id">ID: <?php echo $article['id']; ?></span>
                                            <span class="article-slug">别名: <?php echo htmlspecialchars($article['slug']); ?></span>
                                            <?php if ($article['excerpt']): ?>
                                                <span class="has-excerpt" title="有摘要">
                                                    <i class="fas fa-quote-right"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($article['category_name']): ?>
                                            <span class="category-tag">
                                                <i class="fas fa-folder"></i>
                                                <?php echo htmlspecialchars($article['category_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <i class="fas fa-folder-open"></i>
                                                未分类
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="author-info">
                                            <span class="author-name">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($article['author_name']); ?>
                                            </span>
                                            <?php if ($article['author_id'] == $currentUser['id']): ?>
                                                <span class="author-self" title="自己的文章">
                                                    <i class="fas fa-star"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $article['status']; ?>" 
                                              onclick="toggleStatus(<?php echo $article['id']; ?>, '<?php echo $article['status']; ?>')"
                                              title="点击切换状态">
                                            <?php
                                            $statusLabels = [
                                                'published' => '已发布',
                                                'draft' => '草稿',
                                                'private' => '私密'
                                            ];
                                            $statusIcons = [
                                                'published' => 'fas fa-eye',
                                                'draft' => 'fas fa-edit',
                                                'private' => 'fas fa-lock'
                                            ];
                                            echo '<i class="' . $statusIcons[$article['status']] . '"></i> ';
                                            echo $statusLabels[$article['status']] ?? $article['status'];
                                            ?>
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
                                            <?php if ($article['published_at'] && $article['status'] === 'published'): ?>
                                                <span class="published-date" title="发布时间">
                                                    <i class="fas fa-broadcast-tower"></i>
                                                    <?php echo date('m-d H:i', strtotime($article['published_at'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($auth->hasPermission('article.edit')): ?>
                                                <a href="article_edit.php?id=<?php echo $article['id']; ?>" 
                                                   class="btn-action btn-primary" title="编辑">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="../public/article.php?slug=<?php echo $article['slug']; ?>" 
                                               target="_blank" class="btn-action btn-info" title="查看">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            
                                            <button onclick="copyArticleLink('<?php echo $article['slug']; ?>')" 
                                                    class="btn-action btn-secondary" title="复制链接">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            
                                            <?php if ($auth->hasPermission('article.delete')): ?>
                                                <button onclick="deleteArticle(<?php echo $article['id']; ?>)" 
                                                        class="btn-action btn-danger" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&author=<?php echo urlencode($author); ?>" class="page-btn">
                                    <i class="fas fa-chevron-left"></i> 上一页
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1): ?>
                                <a href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&author=<?php echo urlencode($author); ?>" class="page-btn">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="page-ellipsis">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&author=<?php echo urlencode($author); ?>" 
                                   class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="page-ellipsis">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&author=<?php echo urlencode($author); ?>" class="page-btn"><?php echo $totalPages; ?></a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&author=<?php echo urlencode($author); ?>" class="page-btn">
                                    下一页 <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pagination-info">
                            显示第 <?php echo $offset + 1; ?> 到 <?php echo min($offset + $limit, $totalCount); ?> 条，共 <?php echo $totalCount; ?> 条记录
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
/* 文章管理样式（增强版本） */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(3, 150px) 1fr;
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
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 15px;
    margin-bottom: 1rem;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
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

.content-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafbfc;
}

.card-header h3 {
    margin: 0;
    color: #2c3e50;
    font-weight: 600;
}

.card-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
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
    border-bottom: 2px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 10;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
    white-space: nowrap;
    padding: 1rem; border-bottom: 1px solid #eee; vertical-align: middle; white-space: nowrap; 
}

.data-table tr:hover {
    background: #f8f9fa;
}

.article-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.title-link {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.title-link:hover {
    color: #3498db;
}

.has-image {
    color: #27ae60;
    font-size: 0.8rem;
}

.article-meta {
    font-size: 0.8rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.has-excerpt {
    color: #3498db;
}

.category-tag {
    background: #e9ecef;
    color: #495057;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.author-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.author-name {
    color: #6c757d;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.9rem;
}

.author-self {
    color: #f39c12;
    font-size: 0.7rem;
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    min-width: 80px;
    justify-content: center;
}

.status-published {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-draft {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-private {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.view-count {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.9rem;
    color: #6c757d;
}

.date-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex-direction: row; 
    align-items: center;
}

.date-main {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
}

.date-time {
    font-size: 0.8rem;
    color: #6c757d;
}

.published-date {
    font-size: 0.7rem;
    color: #27ae60;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 6px;
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
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

.btn-action.btn-primary:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.btn-action.btn-info:hover {
    background: #17a2b8;
    color: white;
    border-color: #17a2b8;
}

.btn-action.btn-secondary:hover {
    background: #6c757d;
    color: white;
    border-color: #6c757d;
}

.btn-action.btn-danger:hover {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.5;
    color: #adb5bd;
}

.empty-state h3 {
    color: #495057;
    margin-bottom: 1rem;
}

.empty-state p {
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.pagination-wrapper {
    padding: 1.5rem;
    text-align: center;
    background: #fafbfc;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.pagination {
    display: flex;
    gap: 0.25rem;
    align-items: center;
}

.page-btn {
    padding: 0.5rem 0.75rem;
    border: 1px solid #dee2e6;
    background: white;
    color: #495057;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.page-btn:hover {
    background: #e9ecef;
    color: #495057;
    text-decoration: none;
    transform: translateY(-1px);
}

.page-btn.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.page-ellipsis {
    color: #6c757d;
    padding: 0 0.5rem;
}

.pagination-info {
    color: #6c757d;
    font-size: 0.9rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}

.header-left h1 {
    color: #2c3e50;
    margin: 0 0 0.5rem 0;
    font-size: 1.8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-left p {
    color: #7f8c8d;
    margin: 0;
    font-size: 0.95rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    background: none;
    font-size: 0.9rem;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border-color: #2980b9;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border-color: #6c757d;
}

.btn-secondary:hover {
    background: #545b62;
    text-decoration: none;
    color: white;
}

.btn-info {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    border-color: #138496;
}

.btn-info:hover {
    background: linear-gradient(135deg, #138496, #117a8b);
    color: white;
    text-decoration: none;
}

.btn-success {
    background: linear-gradient(135deg, #27ae60, #229954);
    color: white;
    border-color: #229954;
}

.btn-success:hover {
    background: linear-gradient(135deg, #229954, #1e8449);
    color: white;
    text-decoration: none;
}

.btn-warning {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
    border-color: #e67e22;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e67e22, #d35400);
    color: white;
    text-decoration: none;
}

.btn-danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border-color: #c0392b;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c0392b, #a93226);
    color: white;
    text-decoration: none;
}

.btn-outline {
    background: transparent;
    border: 1px solid #6c757d;
    color: #6c757d;
}

.btn-outline:hover {
    background: #6c757d;
    color: white;
    text-decoration: none;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left-color: #27ae60;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #e74c3c;
}

.alert i {
    font-size: 1.1rem;
}

.text-muted {
    color: #6c757d;
}

@media (max-width: 1200px) {
    .filter-form {
        grid-template-columns: repeat(2, 1fr) 2fr;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .header-actions {
        width: 100%;
        flex-direction: column;
    }

    .header-actions .btn {
        width: 100%;
        justify-content: center;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .search-group {
        flex-direction: column;
    }

    .batch-content {
        flex-direction: column;
        gap: 1rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .pagination-wrapper {
        flex-direction: column;
        text-align: center;
    }

    .data-table {
        font-size: 0.85rem;
    }

    .data-table th,
    .data-table td {
        padding: 0.75rem 0.5rem;
    }

    .action-buttons {
        flex-direction: column;
        gap: 0.2rem;
    }
}

@media (max-width: 480px) {
    .article-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .date-info {
        text-align: center;
    }
}
</style>

<script>
// 文章管理JavaScript功能（增强版本）

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 绑定行选择事件
    document.querySelectorAll('.row-select').forEach(checkbox => {
        checkbox.addEventListener('change', updateBatchActions);
    });
    
    // 表格行双击编辑
    document.querySelectorAll('#articlesTable tbody tr').forEach(row => {
        row.addEventListener('dblclick', function() {
            const editLink = this.querySelector('a[href*="article_edit.php"]');
            if (editLink) {
                window.location.href = editLink.href;
            }
        });
    });
    
    // 自动隐藏消息提示
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

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

// 删除单个文章
function deleteArticle(id) {
    if (confirm('确定要删除这篇文章吗？删除后将移至回收站。')) {
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

// 批量操作
function batchAction(action) {
    const selected = document.querySelectorAll('.row-select:checked');
    const ids = Array.from(selected).map(cb => cb.value);
    
    if (ids.length === 0) {
        showToast('请选择要操作的文章', 'warning');
        return;
    }
    
    let message = '';
    let actionType = '';
    
    switch (action) {
        case 'published':
            message = `确定要发布选中的 ${ids.length} 篇文章吗？`;
            actionType = 'batch_status';
            break;
        case 'draft':
            message = `确定要将选中的 ${ids.length} 篇文章设为草稿吗？`;
            actionType = 'batch_status';
            break;
        case 'private':
            message = `确定要将选中的 ${ids.length} 篇文章设为私密吗？`;
            actionType = 'batch_status';
            break;
        case 'delete':
            message = `确定要删除选中的 ${ids.length} 篇文章吗？删除后将移至回收站。`;
            actionType = 'batch_delete';
            break;
    }
    
    if (confirm(message)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        if (actionType === 'batch_delete') {
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
    }
}

// 切换文章状态
function toggleStatus(id, currentStatus) {
    const statusOptions = {
        'draft': { next: 'published', label: '发布' },
        'published': { next: 'draft', label: '设为草稿' },
        'private': { next: 'published', label: '发布' }
    };
    
    const nextStatus = statusOptions[currentStatus];
    if (!nextStatus) return;
    
    if (confirm(`确定要${nextStatus.label}这篇文章吗？`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.innerHTML = `
            <input name="action" value="toggle_status">
            <input name="id" value="${id}">
            <input name="status" value="${nextStatus.next}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// 复制文章链接
function copyArticleLink(slug) {
    const baseUrl = window.location.origin;
    const articleUrl = `${baseUrl}/public/article.php?slug=${slug}`;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(articleUrl).then(() => {
            showToast('链接已复制到剪贴板', 'success');
        }).catch(() => {
            fallbackCopyTextToClipboard(articleUrl);
        });
    } else {
        fallbackCopyTextToClipboard(articleUrl);
    }
}

// 备用复制方法
function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('链接已复制到剪贴板', 'success');
    } catch (err) {
        showToast('复制失败，请手动复制', 'error');
    }
    
    document.body.removeChild(textArea);
}

// 切换列表视图
function toggleListView() {
    const table = document.getElementById('articlesTable');
    table.classList.toggle('compact-view');
    
    const button = event.target.closest('button');
    const icon = button.querySelector('i');
    
    if (table.classList.contains('compact-view')) {
        icon.className = 'fas fa-th';
        button.title = '切换到详细视图';
    } else {
        icon.className = 'fas fa-th-list';
        button.title = '切换到紧凑视图';
    }
}

// 刷新列表
function refreshList() {
    const button = event.target.closest('button');
    const icon = button.querySelector('i');
    
    icon.classList.add('fa-spin');
    
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// 显示Toast提示
function showToast(message, type = 'info') {
    // 创建或获取toast容器
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(toastContainer);
    }
    
    // 创建toast元素
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : type === 'warning' ? '#f39c12' : '#3498db'};
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
    `;
    toast.textContent = message;
    
    toastContainer.appendChild(toast);
    
    // 显示动画
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);
    
    // 自动隐藏
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// 键盘快捷键
document.addEventListener('keydown', function(e) {
    // Ctrl+N 新建文章
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        window.location.href = 'article_add.php';
    }
    
    // Ctrl+R 刷新列表
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        window.location.reload();
    }
    
    // ESC 清除选择
    if (e.key === 'Escape') {
        clearSelection();
    }
});

// 表格排序功能
function sortTable(column) {
    // 这里可以添加表格排序逻辑
    console.log('Sort by:', column);
}

// 快速筛选
function quickFilter(status) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('status', status);
    currentUrl.searchParams.delete('page'); // 重置页码
    window.location.href = currentUrl.toString();
}
</script>

<?php include '../templates/admin_footer.php'; ?>