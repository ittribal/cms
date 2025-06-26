<?php
// admin/user_list.php - 用户列表页面（修复版本）

// 获取筛选参数
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = ["1=1"];
$params = [];

if ($role && $role !== 'all') {
    $where[] = "role = ?";
    $params[] = $role;
}

if ($status && $status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    // 添加字段存在性检查
    $searchFields = ["username LIKE ?", "email LIKE ?"];
    $searchParams = ["%{$search}%", "%{$search}%"];
    
    // 检查 real_name 字段是否存在
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'real_name'");
    if (!empty($columns)) {
        $searchFields[] = "real_name LIKE ?";
        $searchParams[] = "%{$search}%";
    }
    
    $where[] = "(" . implode(" OR ", $searchFields) . ")";
    $params = array_merge($params, $searchParams);
}

$whereClause = implode(' AND ', $where);

// 获取用户列表 - 动态构建SELECT字段
$baseFields = "u.id, u.username, u.email, u.role, u.status, u.avatar, u.created_at, u.updated_at, u.last_login";

// 检查可选字段是否存在
$optionalFields = [];
$checkFields = ['real_name', 'phone', 'bio', 'website', 'location'];

foreach ($checkFields as $field) {
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE '{$field}'");
    if (!empty($columns)) {
        $optionalFields[] = "u.{$field}";
    }
}

$selectFields = $baseFields;
if (!empty($optionalFields)) {
    $selectFields .= ", " . implode(", ", $optionalFields);
}

$sql = "SELECT {$selectFields}, 
               (SELECT COUNT(*) FROM articles WHERE author_id = u.id) as article_count
        FROM users u 
        WHERE {$whereClause}
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?";

$users = $db->fetchAll($sql, array_merge($params, [$limit, $offset]));

// 获取总数
$countSql = "SELECT COUNT(*) as total FROM users u WHERE {$whereClause}";
$totalCount = $db->fetchOne($countSql, $params)['total'];
$totalPages = ceil($totalCount / $limit);

include '../templates/admin_header.php';
?>

<!-- 引入用户管理专用CSS -->
<link rel="stylesheet" href="css/users.css">

<div class="users-page">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-users"></i> 用户管理</h1>
                <p>管理网站用户，包括角色分配、状态管理等操作</p>
            </div>
            <div class="header-actions">
                <?php if ($auth->hasPermission('user.create')): ?>
                    <a href="users.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新建用户
                    </a>
                <?php endif; ?>
                <button onclick="showImportModal()" class="btn btn-info">
                    <i class="fas fa-upload"></i> 导入用户
                </button>
                <button onclick="exportUsers()" class="btn btn-success">
                    <i class="fas fa-download"></i> 导出用户
                </button>
                <a href="download_template.php" class="btn btn-secondary">
                    <i class="fas fa-file-csv"></i> 下载模板
                </a>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="stats-grid">
            <?php
            $stats = [
                'total' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
                'active' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
                'admin' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role IN ('super_admin', 'admin')")['count'],
                'today' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")['count']
            ];
            ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: #3498db;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">用户总数</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #27ae60;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                    <div class="stat-label">活跃用户</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e74c3c;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['admin']); ?></div>
                    <div class="stat-label">管理员</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f39c12;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['today']); ?></div>
                    <div class="stat-label">今日新增</div>
                </div>
            </div>
        </div>

        <!-- 筛选和搜索 -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <select name="role" class="form-control">
                        <option value="">所有角色</option>
                        <option value="super_admin" <?php echo $role === 'super_admin' ? 'selected' : ''; ?>>超级管理员</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>管理员</option>
                        <option value="editor" <?php echo $role === 'editor' ? 'selected' : ''; ?>>编辑</option>
                        <option value="author" <?php echo $role === 'author' ? 'selected' : ''; ?>>作者</option>
                        <option value="subscriber" <?php echo $role === 'subscriber' ? 'selected' : ''; ?>>订阅者</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="status" class="form-control">
                        <option value="">所有状态</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>活跃</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>禁用</option>
                    </select>
                </div>
                
                <div class="search-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="搜索用户名、邮箱..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 清除
                    </a>
                </div>
            </form>
        </div>

        <!-- 批量操作栏 -->
        <div class="batch-actions" style="display: none;">
            <div class="batch-content">
                <span>已选择 <strong class="selected-count">0</strong> 个用户</span>
                <div class="batch-buttons">
                    <button onclick="batchAction('active')" class="btn btn-sm btn-success">批量启用</button>
                    <button onclick="batchAction('inactive')" class="btn btn-sm btn-warning">批量禁用</button>
                    <button onclick="batchAction('delete')" class="btn btn-sm btn-danger">批量删除</button>
                    <button onclick="clearSelection()" class="btn btn-sm btn-secondary">取消选择</button>
                </div>
            </div>
        </div>

        <!-- 用户列表 -->
        <div class="content-card">
            <div class="card-header">
                <h3>用户列表</h3>
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
                <table class="data-table" id="usersTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="select-all" onchange="toggleSelectAll(this)">
                            </th>
                            <th width="80">头像</th>
                            <th>用户信息</th>
                            <th width="100">角色</th>
                            <th width="80">状态</th>
                            <th width="80">文章数</th>
                            <th width="120">最后登录</th>
                            <th width="120">注册时间</th>
                            <th width="220">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <p>暂无用户数据</p>
                                        <?php if ($auth->hasPermission('user.create')): ?>
                                            <a href="users.php?action=add" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> 创建第一个用户
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr data-id="<?php echo $user['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="row-select" value="<?php echo $user['id']; ?>" 
                                               onchange="updateBatchActions()"
                                               <?php echo $user['id'] == $currentUser['id'] ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <div class="user-avatar">
                                            <?php if (!empty($user['avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                                     alt="头像" class="avatar-img">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name">
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                <?php if (!empty($user['real_name'])): ?>
                                                    <span class="real-name">(<?php echo htmlspecialchars($user['real_name']); ?>)</span>
                                                <?php endif; ?>
                                                <?php if ($user['id'] == $currentUser['id']): ?>
                                                    <span class="current-user-badge">当前用户</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-email">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </div>
                                            <?php if (!empty($user['phone'])): ?>
                                                <div class="user-phone">
                                                    <i class="fas fa-phone"></i>
                                                    <?php echo htmlspecialchars($user['phone']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php
                                            $roleLabels = [
                                                'super_admin' => '超级管理员',
                                                'admin' => '管理员',
                                                'editor' => '编辑',
                                                'author' => '作者',
                                                'subscriber' => '订阅者'
                                            ];
                                            echo $roleLabels[$user['role']] ?? $user['role'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status']; ?>" 
                                              onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                            <?php echo $user['status'] === 'active' ? '活跃' : '禁用'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="article-count">
                                            <i class="fas fa-file-alt"></i>
                                            <?php echo $user['article_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <?php if ($user['last_login']): ?>
                                                <span class="date-main">
                                                    <?php echo date('Y-m-d', strtotime($user['last_login'])); ?>
                                                </span>
                                                <span class="date-time">
                                                    <?php echo date('H:i', strtotime($user['last_login'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">从未登录</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <span class="date-main">
                                                <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                                            </span>
                                            <span class="date-time">
                                                <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="users.php?action=view&id=<?php echo $user['id']; ?>" 
                                               class="btn-action btn-info" title="查看">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($auth->hasPermission('user.edit')): ?>
                                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" 
                                                   class="btn-action btn-primary" title="编辑">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <button onclick="viewPermissions(<?php echo $user['id']; ?>)" 
                                                    class="btn-action btn-secondary" title="查看权限">
                                                <i class="fas fa-shield-alt"></i>
                                            </button>
                                            
                                            <?php if ($auth->hasPermission('user.edit') && $user['id'] != $currentUser['id']): ?>
                                                <button onclick="resetPassword(<?php echo $user['id']; ?>)" 
                                                        class="btn-action btn-warning" title="重置密码">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($auth->hasPermission('user.delete') && $user['id'] != $currentUser['id']): ?>
                                                <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
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
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- 导入模态框 -->
    <div id="importModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-upload"></i> 导入用户</h3>
                <button type="button" class="close" onclick="closeModal('importModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="importFile">选择CSV文件</label>
                        <input type="file" id="importFile" name="import_file" class="form-control" 
                               accept=".csv,.xlsx" required>
                        <small class="form-text">
                            支持CSV格式，必需列：username, email<br>
                            可选列：real_name, phone, role, password, bio
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>导入选项</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="skip_existing" checked>
                                跳过已存在的用户
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="auto_activate" checked>
                                自动激活新用户
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="send_welcome_email">
                                发送欢迎邮件
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="defaultRole">默认角色</label>
                        <select id="defaultRole" name="default_role" class="form-control">
                            <option value="subscriber">订阅者</option>
                            <option value="author">作者</option>
                            <option value="editor">编辑</option>
                        </select>
                    </div>
                    
                    <div class="progress-bar" id="importProgress" style="display: none;">
                        <div class="progress-fill" id="progressFill">0%</div>
                    </div>
                    
                    <div id="importResult" class="import-result" style="display: none;"></div>
                    
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
    
    <!-- 权限查看模态框 -->
    <div id="permissionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-shield-alt"></i> 用户权限</h3>
                <button type="button" class="close" onclick="closeModal('permissionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="permissionContent">
                    <!-- 权限内容将动态加载 -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 用户管理JavaScript功能

// 选择所有复选框
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.row-select:not([disabled])');
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
        showToast('请选择要操作的用户', 'warning');
        return;
    }
    
    let message = '';
    
    switch (action) {
        case 'active':
            message = `确定要启用选中的 ${ids.length} 个用户吗？`;
            break;
        case 'inactive':
            message = `确定要禁用选中的 ${ids.length} 个用户吗？`;
            break;
        case 'delete':
            message = `确定要删除选中的 ${ids.length} 个用户吗？删除后无法恢复。`;
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
                <input name="action" value="toggle_status">
                <input name="status" value="${action}">
                ${ids.map(id => `<input name="ids[]" value="${id}">`).join('')}
            `;
        }
        
        document.body.appendChild(form);
        form.submit();
    });
}

// 删除单个用户
function deleteUser(id) {
    confirmAction('确定要删除这个用户吗？删除后无法恢复。', () => {
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

// 切换用户状态
function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const statusText = newStatus === 'active' ? '启用' : '禁用';
    
    confirmAction(`确定要${statusText}这个用户吗？`, () => {
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

// 重置密码
function resetPassword(id) {
    confirmAction('确定要重置这个用户的密码吗？新密码将随机生成。', () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.innerHTML = `
            <input name="action" value="reset_password">
            <input name="id" value="${id}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    });
}

// 查看用户权限
function viewPermissions(userId) {
    fetch(`get_user_permissions.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('permissionContent').innerHTML = data.html;
                document.getElementById('permissionModal').style.display = 'block';
            } else {
                showToast('获取权限信息失败', 'error');
            }
        })
        .catch(error => {
            showToast('获取权限信息失败', 'error');
        });
}

// 显示导入模态框
function showImportModal() {
    document.getElementById('importModal').style.display = 'block';
}

// 关闭模态框
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// 导出用户功能
function exportUsers() {
    const currentParams = new URLSearchParams(window.location.search);
    const exportUrl = 'export_users.php?' + currentParams.toString();
    window.open(exportUrl, '_blank');
}

// 切换列表视图
function toggleListView() {
    const table = document.getElementById('usersTable');
    table.classList.toggle('compact-view');
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
            
            // 显示进度条
            document.getElementById('importProgress').style.display = 'block';
            document.getElementById('importResult').style.display = 'none';
            
            const progressFill = document.getElementById('progressFill');
            progressFill.style.width = '10%';
            progressFill.textContent = '上传中...';
            
            fetch('import_users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                progressFill.style.width = '100%';
                progressFill.textContent = '完成';
                
                const resultDiv = document.getElementById('importResult');
                resultDiv.style.display = 'block';
                
                if (data.success) {
                    resultDiv.className = 'import-result success';
                    resultDiv.innerHTML = `
                        <h4>导入成功！</h4>
                        <p>${data.message}</p>
                        ${data.results.details ? '<ul>' + data.results.details.map(detail => `<li>${detail}</li>`).join('') + '</ul>' : ''}
                    `;
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    resultDiv.className = 'import-result error';
                    resultDiv.innerHTML = `<h4>导入失败</h4><p>${data.message}</p>`;
                }
            })
            .catch(error => {
                progressFill.style.width = '100%';
                progressFill.textContent = '失败';
                
                const resultDiv = document.getElementById('importResult');
                resultDiv.style.display = 'block';
                resultDiv.className = 'import-result error';
                resultDiv.innerHTML = '<h4>导入失败</h4><p>网络错误，请重试</p>';
            });
        });
    }
    
    // 绑定行选择事件
    document.querySelectorAll('.row-select').forEach(checkbox => {
        checkbox.addEventListener('change', updateBatchActions);
    });
    
    // 表格行双击编辑
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        row.addEventListener('dblclick', function() {
            const editLink = this.querySelector('a[href*="action=edit"]');
            if (editLink) {
                window.location.href = editLink.href;
            }
        });
    });
    
    // 点击外部关闭模态框
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    };
});
</script>

<?php include '../templates/admin_footer.php'; ?>