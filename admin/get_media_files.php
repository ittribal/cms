<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = Database::getInstance();

// 检查登录和权限
if (!$auth->isLoggedIn() || !$auth->hasPermission('media.view')) {
    echo json_encode(['success' => false, 'message' => '没有权限']);
    exit;
}

try {
    $page = max(1, $_GET['page'] ?? 1);
    $limit = min(100, $_GET['limit'] ?? 50);
    $offset = ($page - 1) * $limit;
    
    $type = $_GET['type'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // 构建查询条件
    $where = ['1=1'];
    $params = [];
    
    if ($type === 'image') {
        $where[] = "mime_type LIKE 'image/%'";
    } elseif ($type === 'document') {
        $where[] = "mime_type NOT LIKE 'image/%'";
    }
    
    if ($search) {
        $where[] = "original_name LIKE ?";
        $params[] = "%{$search}%";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 获取文件列表
    $sql = "SELECT m.*, u.username as uploader_name 
            FROM media_files m 
            LEFT JOIN users u ON m.uploaded_by = u.id 
            WHERE {$whereClause}
            ORDER BY m.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $files = $db->fetchAll($sql, array_merge($params, [$limit, $offset]));
    
    // 获取总数
    $countSql = "SELECT COUNT(*) as total FROM media_files m WHERE {$whereClause}";
    $totalCount = $db->fetchOne($countSql, $params)['total'];
    
    // 处理文件路径
    foreach ($files as &$file) {
        // 确保文件路径是相对路径
        if (!file_exists('../' . $file['file_path'])) {
            $file['file_exists'] = false;
        } else {
            $file['file_exists'] = true;
        }
        
        // 添加文件类型信息
        $file['is_image'] = strpos($file['mime_type'], 'image/') === 0;
        $file['file_extension'] = pathinfo($file['original_name'], PATHINFO_EXTENSION);
        
        // 格式化日期
        $file['formatted_date'] = date('Y-m-d H:i', strtotime($file['created_at']));
        
        // 格式化文件大小
        $file['formatted_size'] = formatFileSize($file['file_size']);
    }
    
    echo json_encode([
        'success' => true,
        'files' => $files,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalCount / $limit),
            'total_count' => $totalCount,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '获取文件列表失败：' . $e->getMessage()
    ]);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}