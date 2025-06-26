<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录和权限
if (!$auth->isLoggedIn() || !$auth->hasPermission('user.create')) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => '没有权限']));
}

header('Content-Type: application/json');

try {
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('文件上传失败');
    }
    
    $file = $_FILES['import_file'];
    $skipExisting = isset($_POST['skip_existing']);
    $autoActivate = isset($_POST['auto_activate']);
    $defaultRole = $_POST['default_role'] ?? 'subscriber';
    $sendWelcomeEmail = isset($_POST['send_welcome_email']);
    
    // 验证文件类型
    $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/csv'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes) && !in_array($file['type'], $allowedTypes)) {
        throw new Exception('不支持的文件格式，请上传CSV文件');
    }
    
    // 读取CSV文件
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception('无法读取文件');
    }
    
    // 读取表头
    $headers = fgetcsv($handle);
    if (!$headers) {
        throw new Exception('文件格式错误，无法读取表头');
    }
    
    // 验证必需的列
    $requiredColumns = ['username', 'email'];
    $headerMap = array_flip(array_map('strtolower', $headers));
    
    foreach ($requiredColumns as $column) {
        if (!isset($headerMap[$column])) {
            throw new Exception("缺少必需的列: {$column}");
        }
    }
    
    $results = [
        'total' => 0,
        'success' => 0,
        'skipped' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    while (($row = fgetcsv($handle)) !== FALSE) {
        $results['total']++;
        
        try {
            // 构建用户数据
            $userData = [];
            foreach ($headers as $index => $header) {
                $value = isset($row[$index]) ? trim($row[$index]) : '';
                $userData[strtolower($header)] = $value;
            }
            
            // 验证必填字段
            if (empty($userData['username']) || empty($userData['email'])) {
                throw new Exception('用户名和邮箱不能为空');
            }
            
            // 验证邮箱格式
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('邮箱格式不正确');
            }
            
            // 检查用户是否已存在
            $existing = $db->fetchOne(
                "SELECT id FROM users WHERE username = ? OR email = ?", 
                [$userData['username'], $userData['email']]
            );
            
            if ($existing) {
                if ($skipExisting) {
                    $results['skipped']++;
                    $results['details'][] = "跳过已存在用户: {$userData['username']}";
                    continue;
                } else {
                    throw new Exception('用户名或邮箱已存在');
                }
            }
            
            // 生成随机密码或使用提供的密码
            $password = !empty($userData['password']) ? $userData['password'] : generateRandomPassword();
            
            // 插入用户
            $insertData = [
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'real_name' => $userData['real_name'] ?? '',
                'phone' => $userData['phone'] ?? '',
                'role' => $userData['role'] ?? $defaultRole,
                'status' => $autoActivate ? 'active' : 'inactive',
                'bio' => $userData['bio'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO users (" . implode(', ', array_keys($insertData)) . ") 
                    VALUES (" . str_repeat('?,', count($insertData) - 1) . "?)";
            
            if ($db->execute($sql, array_values($insertData))) {
                $userId = $db->getLastInsertId();
                $results['success']++;
                $results['details'][] = "成功导入用户: {$userData['username']} (密码: {$password})";
                
                // 发送欢迎邮件
                if ($sendWelcomeEmail) {
                    sendWelcomeEmail($userData['email'], $userData['username'], $password);
                }
                
                $auth->logAction('导入用户', "用户ID: {$userId}, 用户名: {$userData['username']}");
            } else {
                throw new Exception('数据库插入失败');
            }
            
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = "第 {$results['total']} 行错误: " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    echo json_encode([
        'success' => true,
        'message' => "导入完成！成功: {$results['success']}, 跳过: {$results['skipped']}, 错误: {$results['errors']}",
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generateRandomPassword($length = 8) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

function sendWelcomeEmail($email, $username, $password) {
    // 这里可以集成邮件服务发送欢迎邮件
    // 暂时记录到日志
    error_log("Welcome email sent to {$email} with username: {$username}, password: {$password}");
}
?>