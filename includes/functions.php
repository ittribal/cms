<?php
// includes/functions.php - 全局公共辅助函数库

// 安全输出HTML
function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// 安全输出URL
function esc_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

// 安全输出属性
function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// 清理输入数据
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // 使用 ENT_QUOTES 处理单双引号
    return $data;
}

// 验证邮箱格式
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 密码强度检查
function check_password_strength($password) {
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = '密码长度至少8位';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = '密码必须包含大写字母';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = '密码必须包含小写字母';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = '密码必须包含数字';
    }
    return $errors;
}

// 生成安全的随机密码
function generate_random_password($length = 12) { 
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return str_shuffle($password); // 打乱字符，增加随机性
}

// URL slug生成 (支持中文)
function generate_slug($text) {
    $text = trim($text);
    $text = preg_replace('/[^\p{L}\p{N}\s\-_.]/u', '', $text); // 允许中文字符、字母、数字、空格、下划线、连字符、点号
    $text = preg_replace('/[\s_]+/', '-', $text); // 将空格和下划线转换为连字符
    $text = preg_replace('/-+/', '-', $text); // 移除多余的连字符
    $text = trim($text, '-'); // 移除首尾的连字符
    return $text ?: 'slug-' . time(); // 防止空slug
}

// 获取文件扩展名
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// 检查文件类型是否允许
function is_allowed_file_type($filename) {
    $extension = get_file_extension($filename);
    return in_array($extension, ALLOWED_FILE_TYPES); // 依赖 config.php 中的 ALLOWED_FILE_TYPES
}

// 文件上传处理 (现在使用 `config.php` 中的常量)
function handle_file_upload($file, $sub_dir = '', $allowed_mimes = []) { // sub_dir 例如 'articles/', 'editor/'
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('文件上传失败，错误代码: ' . $file['error']);
    }
    
    $target_dir = UPLOAD_PATH . $sub_dir;
    ensure_directory_exists($target_dir); // 确保目录存在

    // 检查文件 MIME 类型（更安全）
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!empty($allowed_mimes) && !in_array($mime_type, $allowed_mimes)) {
        throw new Exception('不允许的文件类型: ' . $mime_type);
    }
    
    // 检查文件大小
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('文件大小超过限制: ' . format_file_size(MAX_UPLOAD_SIZE));
    }

    $extension = get_file_extension($file['name']);
    $new_filename = uniqid('file_') . '_' . time() . '.' . $extension; // 更安全的唯一文件名
    $target_file = $target_dir . $new_filename;
    $relative_filepath = 'uploads/' . $sub_dir . $new_filename; // 数据库存储的相对路径

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'filename' => $new_filename,
            'filepath' => $relative_filepath, // 返回相对路径，方便数据库存储和前端访问
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $mime_type
        ];
    }

    throw new Exception('文件保存失败');
}


// 分页处理
function paginate($total_items, $current_page = 1, $items_per_page = ADMIN_ITEMS_PER_PAGE) { // 依赖 config.php 中的 ADMIN_ITEMS_PER_PAGE
    $current_page = max(1, intval($current_page));
    $items_per_page = max(1, intval($items_per_page));
    $total_pages = ceil($total_items / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => max(1, $current_page - 1),
        'next_page' => min($total_pages, $current_page + 1)
    ];
}

// 格式化文件大小 (现在统一为 format_file_size)
function format_file_size($size) { 
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unit = 0;
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    return round($size, 2) . ' ' . $units[$unit];
}

// 时间友好显示
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return '刚刚';
    if ($time < 3600) return floor($time/60) . '分钟前';
    if ($time < 86400) return floor($time/3600) . '小时前';
    if ($time < 2592000) return floor($time/86400) . '天前';
    if ($time < 31536000) return floor($time/2592000) . '个月前';
    return floor($time/31536000) . '年前';
}

// JSON响应
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 闪存消息
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// 安全重定向
function safe_redirect($url, $default = '/admin/system/dashboard.php') { // 默认重定向到仪表盘
    // 仅允许相对路径或同源绝对路径
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        // 检查是否是同源URL
        $parsed_url = parse_url($url);
        $parsed_site_url = parse_url(SITE_URL); // 依赖 config.php 中的 SITE_URL
        if (isset($parsed_url['host']) && $parsed_url['host'] === $parsed_site_url['host']) {
            header("Location: $url");
            exit;
        } else {
            header("Location: $default"); // 开放重定向攻击
            exit;
        }
    }
    header("Location: $url");
    exit;
}

// 确保目录存在 (这个函数通常在 config.php 中调用，用于核心目录创建)
function ensure_directory_exists($path) { 
    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            error_log("Failed to create directory: $path");
            throw new Exception("无法创建目录: $path");
        }
    }
    // 确保可写
    if (!is_writable($path)) {
        if (!chmod($path, 0755)) {
            error_log("Failed to set permissions for directory: $path");
        }
    }
}

// 获取客户端IP地址
function get_client_ip() { 
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// 生成CSRF令牌
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF令牌
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}