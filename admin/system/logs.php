<?php
// admin/system/logs.php - 系统日志

// --- 修复点：确保 config.php 首先被引入，并使用 $_SERVER['DOCUMENT_ROOT'] 或 __DIR__ 向上跳两级 ---
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php'; 
// 如果你的项目根目录不是 Web 服务器的 DOCUMENT_ROOT 根，而是 DOCUMENT_ROOT/cms，则可能需要调整为
// require_once __DIR__ . '/../../includes/config.php'; // 这种方式，但要确保 open_basedir 允许
// 通常 $_SERVER['DOCUMENT_ROOT'] 是最稳妥的，假设你的项目是放在 Web 根目录的。

// 引入其他核心类和函数，它们现在可以安全地使用 ABSPATH 了
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php';
require_once ABSPATH . 'includes/Auth.php';

$db = Database::getInstance();
$auth = Auth::getInstance();

// 检查登录和权限
$auth->requirePermission('system.logs', '您没有权限访问系统日志页面。'); // 假设有 system.logs 权限

$pageTitle = '系统日志'; // 页面标题

// 处理 POST 请求 (例如日志清理)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('CSRF 验证失败，请刷新页面重试。', 'error');
        safe_redirect(SITE_URL . '/admin/system/logs.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'clear_old_logs':
            $auth->requirePermission('system.logs.clear', '您没有权限清理日志。'); // 假设有 system.logs.clear 权限

            $days_to_keep = intval($_POST['days_to_keep'] ?? 30); // 默认保留30天日志
            if ($days_to_keep < 1 || $days_to_keep > 365) {
                set_flash_message('保留天数范围无效，请选择 1 到 365 天。', 'error');
                safe_redirect(SITE_URL . '/admin/system/logs.php');
            }

            try {
                $sql = "DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
                $stmt = $db->getConnection()->prepare($sql);
                $stmt->execute([$days_to_keep]);
                $deleted_count = $stmt->rowCount(); // 获取删除的行数
                
                $auth->logAction($auth->getCurrentUser()['id'], '清理系统日志', 'admin_logs', null, ['days_to_keep' => $days_to_keep, 'deleted_count' => $deleted_count]);
                set_flash_message("成功清理了 {$deleted_count} 条 {$days_to_keep} 天前的日志记录。", 'success');
            } catch (Exception $e) {
                set_flash_message('日志清理失败：' . $e->getMessage(), 'error');
            }
            safe_redirect(SITE_URL . '/admin/system/logs.php');
            break;
    }
}


// 分页和筛选
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = ADMIN_ITEMS_PER_PAGE; // 使用后台通用每页条目数
$action_filter = sanitize_input($_GET['action_filter'] ?? '');
$user_filter_id = intval($_GET['user_filter'] ?? 0); // 注意变量名区分
$date_filter = sanitize_input($_GET['date_filter'] ?? '');

$where_conditions = [];
$params = [];

if (!empty($action_filter)) {
    $where_conditions[] = 'l.action LIKE ?';
    $params[] = "%{$action_filter}%";
}

if ($user_filter_id > 0) {
    $where_conditions[] = 'l.user_id = ?';
    $params[] = $user_filter_id;
}

if (!empty($date_filter)) {
    // 确保日期格式正确，避免 SQL 注入
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
        $where_conditions[] = 'DATE(l.created_at) = ?';
        $params[] = $date_filter;
    } else {
        $date_filter = ''; // 无效日期则清空，避免 SQL 错误
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$total_logs_count = $db->fetchOne("SELECT COUNT(*) as count FROM admin_logs l $where_clause", $params)['count'];
$pagination = paginate($total_logs_count, $page, $per_page);

$logs = $db->fetchAll(
    "SELECT l.*, u.username 
     FROM admin_logs l 
     LEFT JOIN users u ON l.user_id = u.id /* 统一用户表 */
     $where_clause
     ORDER BY l.created_at DESC 
     LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}",
    $params
);

// 获取用户列表用于筛选下拉菜单
$users_for_filter = $db->fetchAll("SELECT id, username FROM users ORDER BY username");

// 获取常见的操作类型 (用于筛选下拉菜单，也可以从数据库 DISTINCT 查询)
$common_actions = $db->fetchAll("SELECT DISTINCT action FROM admin_logs ORDER BY action ASC");

// 获取并显示闪存消息
$flash_message = get_flash_message();

// 引入后台头部模板
include ABSPATH . 'templates/admin_header.php'; 
?>

<main class="content">
    <div class="page-header">
        <h1 class="page-title">系统日志</h1>
        <div class="page-actions">
            <?php if ($auth->hasPermission('system.logs.clear')): ?>
                <button class="btn btn-danger" onclick="showClearLogsModal()">
                    <span class="icon">🗑️</span> 清理旧日志
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_message['type'] ?>">
            <i class="fas fa-<?= $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash_message['message']) ?>
        </div>
    <?php endif; ?>
    
    <div class="content-card mb-4">
        <div class="card-header">
            <h3>日志筛选</h3>
        </div>
        <div class="card-body">
            <div class="filters">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="action_filter" class="sr-only">操作类型</label>
                        <select name="action_filter" id="action_filter" class="form-control">
                            <option value="">所有操作</option>
                            <?php foreach ($common_actions as $action_item): ?>
                                <option value="<?= esc_attr($action_item['action']) ?>" 
                                    <?= ($action_filter === $action_item['action']) ? 'selected' : '' ?>>
                                    <?= esc_html($action_item['action']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="user_filter" class="sr-only">操作用户</label>
                        <select name="user_filter" id="user_filter" class="form-control">
                            <option value="">所有用户</option>
                            <?php foreach ($users_for_filter as $user_item): ?>
                                <option value="<?= esc_attr($user_item['id']) ?>" 
                                    <?= ($user_filter_id == $user_item['id']) ? 'selected' : '' ?>>
                                    <?= esc_html($user_item['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_filter" class="sr-only">日期</label>
                        <input type="date" name="date_filter" id="date_filter" 
                               value="<?= esc_attr($date_filter) ?>" class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">筛选</button>
                    <a href="<?= SITE_URL ?>/admin/system/logs.php" class="btn btn-outline">清除</a>
                </form>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3>日志列表 (总计 <?= number_format($total_logs_count) ?> 条记录)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>暂无日志记录</h3>
                    <p>没有找到符合筛选条件的日志记录。</p>
                </div>
            <?php else: ?>
                <div class="logs-table table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="140">时间</th>
                                <th width="100">用户</th>
                                <th width="120">操作</th>
                                <th width="80">表名</th>
                                <th width="80">记录ID</th>
                                <th width="120">IP地址</th>
                                <th>详情</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="log-time">
                                            <?= date('Y-m-d', strtotime($log['created_at'])) ?><br>
                                            <?= date('H:i:s', strtotime($log['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="user-badge">
                                            <?= esc_html($log['username'] ?? '系统') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="action-badge action-<?= esc_attr(strtolower(str_replace(' ', '', $log['action']))) ?>">
                                            <?= esc_html($log['action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= esc_html($log['table_name'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?= esc_html($log['record_id'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <code><?= esc_html($log['ip_address']) ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['details'])): ?>
                                            <button class="btn btn-sm btn-outline" 
                                                    onclick="showLogDetails('<?= esc_attr(json_encode(json_decode($log['details']), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?>')">
                                                查看详情
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination">
                            <?php 
                            // 构建当前筛选参数，用于分页链接
                            $current_filter_params = [
                                'action_filter' => $action_filter,
                                'user_filter' => $user_filter_id,
                                'date_filter' => $date_filter
                            ];
                            ?>
                            <?php if ($pagination['has_prev']): ?>
                                <a href="?<?= http_build_query(array_merge($current_filter_params, ['page' => $pagination['prev_page']])) ?>" class="page-btn">
                                    <i class="fas fa-chevron-left"></i> 上一页
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($pagination['total_pages'], $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?<?= http_build_query(array_merge($current_filter_params, ['page' => $i])) ?>" 
                                   class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['has_next']): ?>
                                <a href="?<?= http_build_query(array_merge($current_filter_params, ['page' => $pagination['next_page']])) ?>" class="page-btn">
                                    下一页 <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include ABSPATH . 'templates/admin_footer.php'; // 引入后台底部模板 ?>

<div id="logDetailsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>日志详情</h3>
            <button type="button" class="modal-close" onclick="closeModal('logDetailsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <pre id="logDetailsContent" style="white-space: pre-wrap; word-break: break-all;"></pre>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('logDetailsModal')">关闭</button>
        </div>
    </div>
</div>

<div id="clearLogsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>清理旧日志</h3>
            <button type="button" class="modal-close" onclick="closeModal('clearLogsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="clearLogsForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="clear_old_logs">
                <p>您确定要清理多少天前的日志记录？此操作不可恢复。</p>
                <div class="form-group">
                    <label for="days_to_keep">保留最近 (天):</label>
                    <input type="number" id="days_to_keep" name="days_to_keep" class="form-control" value="30" min="1" max="365" required>
                </div>
                <div class="form-actions d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('clearLogsModal')">取消</button>
                    <button type="submit" class="btn btn-danger">确认清理</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* 样式已从 admin/assets/css/logs.css 和 admin/assets/css/admin.css 加载 */
/* 这里仅为方便展示特定样式 */
.logs-table table {
    min-width: 900px; /* 确保表格在小屏幕下能滚动 */
}

.log-time {
    font-size: 0.85rem;
    color: #64748b;
}

.user-badge {
    display: inline-block;
    background: #e2e8f0;
    color: #475569;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

/* 操作徽章 */
.action-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize; /* 首字母大写 */
}

.action-badgelog-in, /* 登录 */
.action-badgecreate {
    background: #d4edda; /* 绿色系 */
    color: #155724;
}
.action-badgelog-out, /* 登出 */
.action-badgedelete {
    background: #f8d7da; /* 红色系 */
    color: #721c24;
}
.action-badgeupdate, /* 更新 */
.action-badgeedit, /* 编辑 */
.action-badgemoderate, /* 审核 */
.action-badgechange, /* 状态改变 */
.action-badgebulk {
    background: #cce5ff; /* 蓝色系 */
    color: #004085;
}
.action-badgemediaupload, /* 媒体上传 */
.action-badgebegins { /* 开始某种操作 */
    background: #fff3cd; /* 黄色系 */
    color: #856404;
}

/* 日志详情模态框 */
#logDetailsContent {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    max-height: 400px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 0.9rem;
    line-height: 1.4;
}

/* 过滤器样式 */
.filters .filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    align-items: flex-end; /* 按钮和输入框底部对齐 */
}
.filters .filter-group {
    flex-grow: 1;
}
.filters .filter-group label.sr-only { /* 屏幕阅读器专用 */
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>

<script>
// JavaScript 逻辑将由 admin/assets/js/logs.js 文件提供
// 这是一个占位符，表示该页面的 JS 逻辑会在这里加载

// 示例函数，实际由 logs.js 提供
function showLogDetails(detailsJsonString) {
    try {
        const parsedDetails = JSON.parse(detailsJsonString);
        // 使用 JSON.stringify 格式化显示，使其可读
        document.getElementById('logDetailsContent').textContent = JSON.stringify(parsedDetails, null, 2);
    } catch (e) {
        // 如果不是有效 JSON，直接显示原始字符串
        document.getElementById('logDetailsContent').textContent = detailsJsonString;
    }
    // 假设 openModal 在 common.js 中定义
    if (typeof openModal === 'function') {
        openModal('logDetailsModal'); 
    } else {
        // 简易回退
        alert('日志详情:\n' + detailsJsonString);
    }
}

function showClearLogsModal() {
    // 假设 openModal 在 common.js 中定义
    if (typeof openModal === 'function') {
        openModal('clearLogsModal'); 
    } else {
        // 简易回退，直接触发 POST 提交
        if (confirm('确定要清理旧日志吗？此操作不可恢复。')) {
            document.getElementById('clearLogsForm').submit();
        }
    }
    document.getElementById('days_to_keep').value = 30; // 默认值
}

// 注意：common.js 应该提供 openModal 和 closeModal 函数
// 并且 apiFetch 和 showToast 也应该来自 common.js
</script>