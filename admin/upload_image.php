<?php
// admin/upload_image.php - 修复路径版本
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// 确保只输出JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只支持POST请求');
    }
    
    // 检查文件上传
    if (!isset($_FILES['upload']) || $_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('文件上传失败');
    }
    
    $file = $_FILES['upload'];
    
    // 简单的文件类型检查
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('不支持的图片格式');
    }
    
    // 文件大小检查 (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('图片大小不能超过5MB');
    }
    
    // 创建上传目录 - 注意这里的路径
    $uploadDir = '../uploads/editor/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 生成文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($extension)) {
        $extension = 'jpg';
    }
    $filename = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('文件保存失败');
    }
    
    // 构建正确的访问URL
    // 从admin目录访问上级目录的uploads
    $accessUrl = '../uploads/editor/' . $filename;
    
    // 清除任何可能的输出
    ob_clean();
    
    // 返回成功响应
    echo json_encode([
        'success' => true,
        'url' => $accessUrl,  // 修正了访问路径
        'filename' => $filename,
        'size' => $file['size'],
        'message' => '上传成功'
    ]);
    
} catch (Exception $e) {
    // 清除任何可能的输出
    ob_clean();
    
    // 返回错误响应
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// 确保脚本结束
exit;
?>