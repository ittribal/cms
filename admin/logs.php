<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录状态和权限
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!$auth->hasPermission('log.view')) {
    die('您没有权限访问此页面');
}

$pageTitle = '操作日志';
$currentUser = $auth->getCurrentUser();

// 获取筛选参数
$user_id = $_GET['user_id'] ?? '';
$action = $_GET['action_filter'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($user_id) {
    $where[] = "al.user_id = ?";
    $params[] = $user_id;
}

if ($action) {
    $where[] = "al.action LIKE ?";
    $params[] = "%{$action}%";
}

if ($date_from) {
    $where[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

$whereClause = implode(' AND ', $where);

// 获取日志列表
$sql = "SELECT al.*, u.username, u.email 
        FROM admin_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE {$whereClause}
        ORDER BY al.created_at DESC 
        LIMIT ? OFFSET ?";

$logs = $db->fetchAll($sql, array_merge($params, [$limit, $offset]));

// 获取总数
$countSql = "SELECT COUNT(*) as total FROM admin_logs al WHERE {$whereClause}";
$totalCount = $db->fetchOne($countSql, $params)['total'];
$totalPages = ceil($totalCount / $limit);

// 获取统计数据
$stats = [
    'total_logs' => $db->fetchOne("SELECT COUNT(*) as count FROM admin_logs")['count'],
    'today_logs' => $db->fetchOne("SELECT COUNT(*) as count FROM admin_logs WHERE DATE(created_at) = CURDATE()")['count'],
    'unique_users' => $db->fetchOne("SELECT COUNT(DISTINCT user_id) as count FROM admin_logs WHERE user_id IS NOT NULL")['count'],
    'total_actions' => $db->fetchOne("SELECT COUNT(DISTINCT action) as count FROM admin_logs")['count']
];

// 获取热门操作
$popularActions = $db->fetchAll("
    SELECT action, COUNT(*) as count 
    FROM admin_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY action 
    ORDER BY count DESC 
    LIMIT 10
");

// 获取活跃用户
$activeUsers = $db->fetchAll("
    SELECT u.username, COUNT(al.id) as log_count 
    FROM admin_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND u.username IS NOT NULL
    GROUP BY al.user_id, u.username 
    ORDER BY log_count DESC 
    LIMIT 10
");

include '../templates/admin_header.php';
?>

<link rel="stylesheet" href="css/logs.css">

<div class="logs-page">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-clipboard-list"></i> 操作日志</h1>
                <p>查看系统操作记录，监控用户活动和系统安全</p>
            </div>
            <div class="header-actions">
                <button onclick="exportLogs()" class="btn btn-success">
                    <i class="fas fa-download"></i> 导出日志
                </button>
                <button onclick="refreshLogs()" class="btn btn-info">
                    <i class="fas fa-sync-alt"></i> 刷新
                </button>
            </div>
        </div>

        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #3498db;">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['total_logs']); ?></div>
                    <div class="stat-label">总日志数</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #27ae60;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['today_logs']); ?></div>
                    <div class="stat-label">今日操作</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e74c3c;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['unique_users']); ?></div>
                    <div class="stat-label">活跃用户</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f39c12;">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['total_actions']); ?></div>
                    <div class="stat-label">操作类型</div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <!-- 日志列表 -->
            <div class="main-panel">
                <!-- 筛选器 -->
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>用户</label>
                                <select name="user_id" class="form-control">
                                    <option value="">所有用户</option>
                                    <?php
                                    $users = $db->fetchAll("SELECT DISTINCT u.id, u.username FROM users u INNER JOIN admin_logs al ON u.id = al.user_id ORDER BY u.username");
                                    foreach ($users as $user) {
                                        $selected = $user_id == $user['id'] ? 'selected' : '';
                                        echo "<option value='{$user['id']}' {$selected}>{$user['username']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>操作类型</label>
                                <input type="text" name="action_filter" class="form-control" 
                                       placeholder="操作关键词" value="<?php echo htmlspecialchars($action); ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label>开始日期</label>
                                <input type="date" name="date_from" class="form-control" 
                                       value="<?php echo $date_from; ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label>结束日期</label>
                                <input type="date" name="date_to" class="form-control" 
                                       value="<?php echo $date_to; ?>">
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 筛选
                                </button>
                                <a href="logs.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> 清除
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- 日志表格 -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>操作记录</h3>
                        <div class="card-info">
                            共 <strong><?php echo number_format($totalCount); ?></strong> 条记录
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th width="120">时间</th>
                                    <th width="100">用户</th>
                                    <th width="120">操作</th>
                                    <th>详情</th>
                                    <th width="120">IP地址</th>
                                    <th width="80">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard-list"></i>
                                                <p>暂无日志记录</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="log-item">
                                            <td>
                                                <div class="log-time">
                                                    <div class="time-date"><?php echo date('m-d', strtotime($log['created_at'])); ?></div>
                                                    <div class="time-hour"><?php echo date('H:i', strtotime($log['created_at'])); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="log-user">
                                                    <?php if ($log['username']): ?>
                                                        <div class="user-avatar">
                                                            <?php echo strtoupper(substr($log['username'], 0, 2)); ?>
                                                        </div>
                                                        <div class="user-name"><?php echo htmlspecialchars($log['username']); ?></div>
                                                    <?php else: ?>
                                                        <span class="text-muted">系统</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="action-badge action-<?php echo getActionType($log['action']); ?>">
                                                    <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="log-details">
                                                    <?php if ($log['table_name']): ?>
                                                        <div class="detail-table">
                                                            <i class="fas fa-table"></i>
                                                            <?php echo htmlspecialchars($log['table_name']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($log['details']): ?>
                                                        <div class="detail-content" title="<?php echo htmlspecialchars($log['details']); ?>">
                                                            <?php echo htmlspecialchars(substr($log['details'], 0, 100)); ?>
                                                            <?php if (strlen($log['details']) > 100): ?>...<?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="log-ip">
                                                    <i class="fas fa-globe"></i>
                                                    <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <button onclick="viewLogDetail(<?php echo $log['id']; ?>)" 
                                                        class="btn-action btn-info" title="查看详情">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
            </div>

            <!-- 侧边栏 -->
            <div class="sidebar-panel">
                <!-- 热门操作 -->
                <div class="content-card">
                    <div class="card-header">
                        <h4><i class="fas fa-fire"></i> 热门操作 (7天)</h4>
                    </div>
                    <div class="popular-actions">
                        <?php foreach ($popularActions as $index => $actionData): ?>
                            <div class="action-item">
                                <div class="action-rank"><?php echo $index + 1; ?></div>
                                <div class="action-info">
                                    <div class="action-name"><?php echo htmlspecialchars($actionData['action']); ?></div>
                                    <div class="action-count"><?php echo $actionData['count']; ?> 次</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 活跃用户 -->
                <div class="content-card">
                    <div class="card-header">
                        <h4><i class="fas fa-user-friends"></i> 活跃用户 (7天)</h4>
                    </div>
                    <div class="active-users">
                        <?php foreach ($activeUsers as $index => $userData): ?>
                            <div class="user-item">
                                <div class="user-rank"><?php echo $index + 1; ?></div>
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($userData['username'], 0, 2)); ?>
                                </div>
                                <div class="user-info">
                                    <div class="user-name"><?php echo htmlspecialchars($userData['username']); ?></div>
                                    <div class="user-count"><?php echo $userData['log_count']; ?> 次操作</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 日志级别说明 -->
                <div class="content-card">
                    <div class="card-header">
                        <h4><i class="fas fa-info-circle"></i> 操作类型</h4>
                    </div>
                    <div class="action-legend">
                        <div class="legend-item">
                            <span class="action-badge action-create">创建</span>
                            <span>新增数据操作</span>
                        </div>
                        <div class="legend-item">
                            <span class="action-badge action-update">更新</span>
                            <span>修改数据操作</span>
                        </div>
                        <div class="legend-item">
                            <span class="action-badge action-delete">删除</span>
                            <span>删除数据操作</span>
                        </div>
                        <div class="legend-item">
                            <span class="action-badge action-login">登录</span>
                            <span>用户登录操作</span>
                        </div>
                        <div class="legend-item">
                            <span class="action-badge action-system">系统</span>
                            <span>系统级别操作</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 日志详情模态框 -->
    <div id="logDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> 日志详情</h3>
                <button type="button" class="close" onclick="closeModal('logDetailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="logDetailContent"></div>
            </div>
        </div>
    </div>
</div>

<script src="js/logs.js"></script>

<?php
// 根据操作类型返回样式类
function getActionType($action) {
    if (strpos($action, '添加') !== false || strpos($action, '创建') !== false) {
        return 'create';
    } elseif (strpos($action, '更新') !== false || strpos($action, '编辑') !== false || strpos($action, '修改') !== false) {
        return 'update';
    } elseif (strpos($action, '删除') !== false) {
        return 'delete';
    } elseif (strpos($action, '登录') !== false) {
        return 'login';
    } else {
        return 'system';
    }
}

include '../templates/admin_footer.php';
?>