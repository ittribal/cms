<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录和权限
if (!$auth->isLoggedIn() || !$auth->hasPermission('user.view')) {
    die('没有权限');
}

try {
    // 获取筛选参数
    $role = $_GET['role'] ?? '';
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $format = $_GET['format'] ?? 'csv';
    
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
        $where[] = "(username LIKE ? OR email LIKE ? OR real_name LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 查询用户数据
    $sql = "SELECT 
                id,
                username,
                email,
                real_name,
                phone,
                role,
                status,
                bio,
                created_at,
                updated_at,
                last_login,
                (SELECT COUNT(*) FROM articles WHERE author_id = users.id) as article_count
            FROM users 
            WHERE {$whereClause}
            ORDER BY created_at DESC";
    
    $users = $db->fetchAll($sql, $params);
    
    if (empty($users)) {
        die('没有数据可导出');
    }
    
    $auth->logAction('导出用户', '导出用户数量: ' . count($users));
    
    if ($format === 'excel') {
        exportToExcel($users);
    } else {
        exportToCSV($users);
    }
    
} catch (Exception $e) {
    die('导出失败: ' . $e->getMessage());
}

function exportToCSV($users) {
    $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // 输出BOM以支持中文
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // 写入表头
    $headers = [
        'ID', '用户名', '邮箱', '真实姓名', '电话', '角色', '状态', 
        '个人简介', '文章数量', '注册时间', '最后登录'
    ];
    fputcsv($output, $headers);
    
    // 角色映射
    $roleLabels = [
        'super_admin' => '超级管理员',
        'admin' => '管理员',
        'editor' => '编辑',
        'author' => '作者',
        'subscriber' => '订阅者'
    ];
    
    // 写入数据
    foreach ($users as $user) {
        $row = [
            $user['id'],
            $user['username'],
            $user['email'],
            $user['real_name'] ?? '',
            $user['phone'] ?? '',
            $roleLabels[$user['role']] ?? $user['role'],
            $user['status'] === 'active' ? '活跃' : '禁用',
            $user['bio'] ?? '',
            $user['article_count'],
            $user['created_at'],
            $user['last_login'] ?: '从未登录'
        ];
        fputcsv($output, $row);
    }
    
    fclose($output);
}

function exportToExcel($users) {
    // 简化的Excel导出（实际项目中建议使用PHPSpreadsheet）
    $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    echo "\xEF\xBB\xBF"; // BOM
    
    echo '<table border="1">';
    
    // 表头
    echo '<tr>';
    echo '<th>ID</th><th>用户名</th><th>邮箱</th><th>真实姓名</th><th>电话</th>';
    echo '<th>角色</th><th>状态</th><th>个人简介</th><th>文章数量</th>';
    echo '<th>注册时间</th><th>最后登录</th>';
    echo '</tr>';
    
    // 角色映射
    $roleLabels = [
        'super_admin' => '超级管理员',
        'admin' => '管理员',
        'editor' => '编辑',
        'author' => '作者',
        'subscriber' => '订阅者'
    ];
    
    // 数据行
    foreach ($users as $user) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($user['id']) . '</td>';
        echo '<td>' . htmlspecialchars($user['username']) . '</td>';
        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
        echo '<td>' . htmlspecialchars($user['real_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($user['phone'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($roleLabels[$user['role']] ?? $user['role']) . '</td>';
        echo '<td>' . ($user['status'] === 'active' ? '活跃' : '禁用') . '</td>';
        echo '<td>' . htmlspecialchars($user['bio'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($user['article_count']) . '</td>';
        echo '<td>' . htmlspecialchars($user['created_at']) . '</td>';
        echo '<td>' . htmlspecialchars($user['last_login'] ?: '从未登录') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}
?>