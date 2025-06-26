<?php
// ==================== admin/content/categories.php - 分类管理 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('content.view');

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        json_response(['success' => false, 'message' => 'CSRF验证失败'], 400);
    }
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add':
            $auth->requirePermission('content.create');
            $name = sanitize_input($_POST['name'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            
            if (empty($name)) {
                json_response(['success' => false, 'message' => '分类名称不能为空'], 400);
            }
            
            $slug = generate_slug($name);
            
            // 检查名称是否存在
            $existing = $db->fetchOne("SELECT id FROM categories WHERE name = ?", [$name]);
            if ($existing) {
                json_response(['success' => false, 'message' => '分类名称已存在'], 400);
            }
            
            try {
                $category_id = $db->insert('categories', [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'sort_order' => 0
                ]);
                
                $auth->logAction($_SESSION['user_id'], 'category_create', 'categories', $category_id);
                json_response(['success' => true, 'message' => '分类添加成功']);
            } catch (Exception $e) {
                json_response(['success' => false, 'message' => '添加失败: ' . $e->getMessage()], 500);
            }
            break;
            
        case 'edit':
            $auth->requirePermission('content.edit');
            $id = intval($_POST['id'] ?? 0);
            $name = sanitize_input($_POST['name'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            
            if (!$id || empty($name)) {
                json_response(['success' => false, 'message' => '参数错误'], 400);
            }
            
            // 检查名称是否存在（排除当前分类）
            $existing = $db->fetchOne("SELECT id FROM categories WHERE name = ? AND id != ?", [$name, $id]);
            if ($existing) {
                json_response(['success' => false, 'message' => '分类名称已存在'], 400);
            }
            
            try {
                $slug = generate_slug($name);
                $db->update('categories', [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description
                ], "id = $id");
                
                $auth->logAction($_SESSION['user_id'], 'category_update', 'categories', $id);
                json_response(['success' => true, 'message' => '分类更新成功']);
            } catch (Exception $e) {
                json_response(['success' => false, 'message' => '更新失败: ' . $e->getMessage()], 500);
            }
            break;
            
        case 'delete':
            $auth->requirePermission('content.delete');
            $id = intval($_POST['id'] ?? 0);
            
            if (!$id) {
                json_response(['success' => false, 'message' => '参数错误'], 400);
            }
            
            // 检查是否有文章使用此分类
            $article_count = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE category_id = ?", [$id])['count'];
            if ($article_count > 0) {
                json_response(['success' => false, 'message' => "此分类下还有 {$article_count} 篇文章，无法删除"], 400);
            }
            
            try {
                $db->delete('categories', 'id = ?', [$id]);
                $auth->logAction($_SESSION['user_id'], 'category_delete', 'categories', $id);
                json_response(['success' => true, 'message' => '分类删除成功']);
            } catch (Exception $e) {
                json_response(['success' => false, 'message' => '删除失败: ' . $e->getMessage()], 500);
            }
            break;
            
        case 'toggle_status':
            $auth->requirePermission('content.edit');
            $id = intval($_POST['id'] ?? 0);
            $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
            
            if (!$id) {
                json_response(['success' => false, 'message' => '参数错误'], 400);
            }
            
            try {
                $db->update('categories', ['status' => $status], "id = $id");
                $auth->logAction($_SESSION['user_id'], 'category_status_change', 'categories', $id);
                json_response(['success' => true, 'message' => '状态更新成功']);
            } catch (Exception $e) {
                json_response(['success' => false, 'message' => '更新失败: ' . $e->getMessage()], 500);
            }
            break;
    }
}

// 获取分类列表
$categories = $db->fetchAll(
    "SELECT c.*, COUNT(a.id) as article_count 
     FROM categories c 
     LEFT JOIN articles a ON c.id = a.category_id 
     GROUP BY c.id 
     ORDER BY c.sort_order ASC, c.name ASC"
);

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">分类管理</h1>
                <div class="page-actions">
                    <?php if ($auth->hasPermission('content.create')): ?>
                        <button class="btn btn-primary" onclick="openAddModal()">
                            <span class="icon">📂</span> 添加分类
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>
            
            <div class="content-card">
                <?php if (!empty($categories)): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>分类名称</th>
                                    <th>Slug</th>
                                    <th>描述</th>
                                    <th>文章数量</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($category['name']) ?></strong>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($category['slug']) ?></code>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($category['description'] ?: '无描述') ?>
                                        </td>
                                        <td>
                                            <span class="article-count"><?= $category['article_count'] ?></span>
                                        </td>
                                        <td>
                                            <label class="status-toggle">
                                                <input type="checkbox" 
                                                       <?= $category['status'] === 'active' ? 'checked' : '' ?>
                                                       onchange="toggleStatus(<?= $category['id'] ?>, this.checked)"
                                                       <?= !$auth->hasPermission('content.edit') ? 'disabled' : '' ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td><?= date('Y-m-d H:i', strtotime($category['created_at'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($auth->hasPermission('content.edit')): ?>
                                                    <button class="btn btn-sm btn-outline" 
                                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($category)) ?>)">
                                                        编辑
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($auth->hasPermission('content.delete')): ?>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')"
                                                            <?= $category['article_count'] > 0 ? 'disabled title="此分类下还有文章，无法删除"' : '' ?>>
                                                        删除
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📂</div>
                        <h3>暂无分类</h3>
                        <p>还没有创建任何分类，现在就添加第一个分类吧！</p>
                        <?php if ($auth->hasPermission('content.create')): ?>
                            <button class="btn btn-primary" onclick="openAddModal()">创建第一个分类</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- 添加/编辑分类模态框 -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">添加分类</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="categoryForm">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="categoryName">分类名称 *</label>
                        <input type="text" id="categoryName" name="name" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="categoryDescription">描述</label>
                        <textarea id="categoryDescription" name="description" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        .article-count {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        
        .status-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #22c55e;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        
        input:disabled + .toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* 模态框样式 */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background-color 0.2s ease;
        }
        
        .modal-close:hover {
            background-color: #f1f5f9;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
    </style>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = '添加分类';
            document.querySelector('[name="action"]').value = 'add';
            document.querySelector('[name="id"]').value = '';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModal').style.display = 'block';
        }
        
        function openEditModal(category) {
            document.getElementById('modalTitle').textContent = '编辑分类';
            document.querySelector('[name="action"]').value = 'edit';
            document.querySelector('[name="id"]').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }
        
        function toggleStatus(id, isActive) {
            const status = isActive ? 'active' : 'inactive';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'toggle_status',
                    'id': id,
                    'status': status,
                    'csrf_token': '<?= generate_csrf_token() ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message, 'error');
                    // 恢复开关状态
                    event.target.checked = !isActive;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('操作失败', 'error');
                event.target.checked = !isActive;
            });
        }
        
        function deleteCategory(id, name) {
            if (!confirm(`确定要删除分类"${name}"吗？`)) {
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'delete',
                    'id': id,
                    'csrf_token': '<?= generate_csrf_token() ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('删除失败', 'error');
            });
        }
        
        function showMessage(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            alertDiv.style.animation = 'fadeInRight 0.3s ease';
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.animation = 'fadeOutRight 0.3s ease';
                setTimeout(() => alertDiv.remove(), 300);
            }, 3000);
        }
        
        // 表单提交处理
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('操作失败', 'error');
            });
        });
        
        // 点击模态框外部关闭
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // 添加动画样式
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInRight {
                from { opacity: 0; transform: translateX(100%); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes fadeOutRight {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(100%); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>