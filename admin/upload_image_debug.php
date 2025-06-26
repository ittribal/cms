<?php
// admin/upload_image_debug.php - 调试版本
// 用于查看服务器实际返回的内容

// 记录所有信息到日志文件
$logFile = '../uploads/upload_debug.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// 记录请求信息
$debugInfo = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'files_data' => $_FILES,
    'headers' => getallheaders(),
    'php_errors' => error_get_last()
];

file_put_contents($logFile, "=== DEBUG START ===\n" . print_r($debugInfo, true) . "\n", FILE_APPEND);

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// 开始输出缓冲
ob_start();

try {
    // 简单检查
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只支持POST请求');
    }
    
    if (!isset($_FILES['upload'])) {
        throw new Exception('没有找到上传文件');
    }
    
    $file = $_FILES['upload'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件太大(php.ini限制)',
            UPLOAD_ERR_FORM_SIZE => '文件太大(表单限制)',
            UPLOAD_ERR_PARTIAL => '文件部分上传',
            UPLOAD_ERR_NO_FILE => '没有文件',
            UPLOAD_ERR_NO_TMP_DIR => '临时目录不存在',
            UPLOAD_ERR_CANT_WRITE => '写入失败',
            UPLOAD_ERR_EXTENSION => '扩展阻止上传'
        ];
        throw new Exception($errors[$file['error']] ?? '上传错误: ' . $file['error']);
    }
    
    // 创建上传目录
    $uploadDir = '../uploads/editor/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('无法创建上传目录');
        }
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
        throw new Exception('文件移动失败');
    }
    
    // 成功响应
    $response = [
        'success' => true,
        'url' => 'uploads/editor/' . $filename,
        'filename' => $filename,
        'size' => $file['size'],
        'message' => '上传成功'
    ];
    
    // 记录成功信息
    file_put_contents($logFile, "SUCCESS: " . print_r($response, true) . "\n", FILE_APPEND);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ];
    
    // 记录错误信息
    file_put_contents($logFile, "ERROR: " . print_r($response, true) . "\n", FILE_APPEND);
}

// 清除输出缓冲中的任何内容
ob_clean();

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE);

// 记录最终输出
file_put_contents($logFile, "FINAL OUTPUT: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n=== DEBUG END ===\n\n", FILE_APPEND);

exit;
?>