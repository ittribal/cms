
<?php
// ==================== admin/content/articles.php - 文章管理 ====================
// admin/content/articles.php - 文章列表与操作控制器

// config.php 应该由主入口文件（如 admin/dashboard.php 或 admin/index.php）首先引入，并定义 ABSPATH
// 因此这里可以直接使用 ABSPATH
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php';
require_once ABSPATH . 'includes/Auth.php'; 

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('content.view');

// 处理批量操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $auth->requirePermission('content.edit');
    
    $action = $_POST['bulk_action'];
    $selected_ids = $_POST['selected_articles'] ?? [];
    
    if (!empty($selected_ids) && verify_csrf_token($_POST['csrf_token'])) {
        $ids_placeholder = implode(',', array_fill(0, count($selected_ids), '?'));
        
        switch ($action) {
            case 'publish':
                $db->query("UPDATE articles SET status = 'published', published_at = NOW() WHERE id IN ($ids_placeholder)", $selected_ids);
                set_flash_message('文章已批量发布', 'success');
                break;
                
            case 'draft':
                $db->query("UPDATE articles SET status = 'draft' WHERE id IN ($ids_placeholder)", $selected_ids);
                set_flash_message('文章已批量设为草稿', 'success');
                break;
                
            case 'delete':
                $auth->requirePermission('content.delete');
                $db->query("DELETE FROM articles WHERE id IN ($ids_placeholder)", $selected_ids);
                set_flash_message('文章已批量删除', 'success');
                break;
        }
        
        $auth->logAction($_SESSION['user_id'], 'bulk_' . $action, 'articles', null, ['count' => count($selected_ids)]);
    }
    
    header('Location: articles.php');
    exit;
}

// 分页和筛选
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$search = sanitize_input($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$category_filter = intval($_GET['category'] ?? 0);

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = '(a.title LIKE ? OR a.content LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = 'a.status = ?';
    $params[] = $status_filter;
}

if ($category_filter > 0) {
    $where_conditions[] = 'a.category_id = ?';
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$total = $db->fetchOne("SELECT COUNT(*) as count FROM articles a $where_clause", $params)['count'];
$pagination = paginate($total, $page, $per_page);

$articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name, u.username as author_name 
     FROM articles a 
     LEFT JOIN categories c ON a.category_id = c.id 
     LEFT JOIN admin_users u ON a.author_id = u.id 
     $where_clause
     ORDER BY a.created_at DESC 
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

// 获取分类列表
$categories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章管理 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">文章管理</h1>
                <div class="page-actions">
                    <?php if ($auth->hasPermission('content.create')): ?>
                        <a href="article_add.php" class="btn btn-primary">
                            <span class="icon">📝</span> 写文章
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="filters">
                    <form method="GET" action="" class="filter-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="搜索文章标题或内容..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <select name="status">
                                <option value="">所有状态</option>
                                <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>已发布</option>
                                <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>草稿</option>
                                <option value="archived" <?= $status_filter === 'archived' ? 'selected' : '' ?>>已归档</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <select name="category">
                                <option value="">所有分类</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">筛选</button>
                        <a href="articles.php" class="btn btn-outline">清除</a>
                    </form>
                </div>
                
                <?php if (!empty($articles)): ?>
                    <form method="POST" action="" id="bulk-form">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="bulk-actions">
                            <select name="bulk_action">
                                <option value="">批量操作</option>
                                <?php if ($auth->hasPermission('content.edit')): ?>
                                    <option value="publish">发布</option>
                                    <option value="draft">设为草稿</option>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('content.delete')): ?>
                                    <option value="delete">删除</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" class="btn btn-secondary" onclick="return confirmBulkAction()">执行</button>
                        </div>
                        
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="select-all">
                                        </th>
                                        <th>标题</th>
                                        <th>分类</th>
                                        <th>作者</th>
                                        <th>状态</th>
                                        <th>浏览量</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($articles as $article): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_articles[]" value="<?= $article['id'] ?>" class="article-checkbox">
                                            </td>
                                            <td>
                                                <div class="article-title-cell">
                                                    <a href="article_edit.php?id=<?= $article['id'] ?>" class="article-link">
                                                        <?= htmlspecialchars($article['title']) ?>
                                                    </a>
                                                    <?php if ($article['featured_image']): ?>
                                                        <span class="has-image" title="有特色图片">🖼️</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($article['category_name'] ?? '未分类') ?>
                                            </td>
                                            <td><?= htmlspecialchars($article['author_name']) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $article['status'] ?>">
                                                    <?php
                                                    switch($article['status']) {
                                                        case 'published': echo '已发布'; break;
                                                        case 'draft': echo '草稿'; break;
                                                        case 'archived': echo '已归档'; break;
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($article['views']) ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($article['created_at'])) ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="../../../?article=<?= $article['slug'] ?>" target="_blank" 
                                                       class="btn btn-sm btn-outline" title="预览">👁️</a>
                                                    <?php if ($auth->hasPermission('content.edit')): ?>
                                                        <a href="article_edit.php?id=<?= $article['id'] ?>" 
                                                           class="btn btn-sm btn-outline">编辑</a>
                                                    <?php endif; ?>
                                                    <?php if ($auth->hasPermission('content.delete')): ?>
                                                        <a href="article_delete.php?id=<?= $article['id'] ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('确定要删除此文章吗？')">删除</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <h3>暂无文章</h3>
                        <p>还没有创建任何文章，现在就开始写作吧！</p>
                        <?php if ($auth->hasPermission('content.create')): ?>
                            <a href="article_add.php" class="btn btn-primary">创建第一篇文章</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?page=<?= $pagination['prev_page'] ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&category=<?= $category_filter ?>">上一页</a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            第 <?= $pagination['current_page'] ?> 页，共 <?= $pagination['total_pages'] ?> 页 (总计 <?= $total ?> 篇文章)
                        </span>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?page=<?= $pagination['next_page'] ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&category=<?= $category_filter ?>">下一页</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // 全选功能
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.article-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
        
        // 批量操作确认
        function confirmBulkAction() {
            const selected = document.querySelectorAll('.article-checkbox:checked');
            if (selected.length === 0) {
                alert('请选择要操作的文章');
                return false;
            }
            
            const action = document.querySelector('[name="bulk_action"]').value;
            if (!action) {
                alert('请选择要执行的操作');
                return false;
            }
            
            const actionNames = {
                'publish': '发布',
                'draft': '设为草稿',
                'delete': '删除'
            };
            
            return confirm(`确定要${actionNames[action]} ${selected.length} 篇文章吗？`);
        }
    </script>
</body>
</html>