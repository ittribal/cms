<?php
// admin/system/backup.php - 数据备份

// --- 修复点：确保 config.php 首先被引入，并使用 $_SERVER['DOCUMENT_ROOT'] ---
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php'; 

// 引入其他核心类和函数，它们现在可以安全地使用 ABSPATH 了
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php';
require_once ABSPATH . 'includes/Auth.php';

$db = Database::getInstance();
$auth = Auth::getInstance();

// 检查登录和权限
$auth->requirePermission('system.backup', '您没有权限访问数据备份页面。'); // 假设有 system.backup 权限

$pageTitle = '数据备份'; // 页面标题

// 处理 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('CSRF 验证失败，请刷新页面重试。', 'error');
        safe_redirect(SITE_URL . '/admin/system/backup.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_backup':
            try {
                $backup_dir = ABSPATH . 'backups/';
                ensure_directory_exists($backup_dir); // 确保备份目录存在
                
                $timestamp = date('Y-m-d_H-i-s');
                $sql_backup_file = $backup_dir . "backup_{$timestamp}.sql";
                $zip_backup_file = $backup_dir . "backup_{$timestamp}.zip";

                // 生成数据库备份内容
                $backup_content = generateDatabaseBackup($db); // 调用辅助函数生成 SQL
                file_put_contents($sql_backup_file, $backup_content);
                
                $final_backup_file_path = $sql_backup_file; // 默认是 SQL 文件

                // 如果 ZipArchive 扩展可用，则尝试压缩数据库备份和 uploads 目录
                if (extension_loaded('zip')) {
                    $zip = new ZipArchive();
                    
                    if ($zip->open($zip_backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                        // 添加数据库 SQL 文件到 ZIP
                        $zip->addFile($sql_backup_file, basename($sql_backup_file));
                        
                        // 添加 uploads 目录下的所有文件到 ZIP
                        $upload_dir_real_path = realpath(UPLOAD_PATH);
                        if ($upload_dir_real_path && is_dir($upload_dir_real_path)) {
                            $files = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($upload_dir_real_path, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::LEAVES_ONLY
                            );
                            
                            foreach ($files as $name => $file) {
                                if (!$file->isDir()) {
                                    $filePath = $file->getRealPath();
                                    // 计算在 ZIP 中的相对路径，例如 'uploads/media/image.jpg'
                                    $relativePath = 'uploads/' . ltrim(str_replace($upload_dir_real_path, '', $filePath), DIRECTORY_SEPARATOR);
                                    $zip->addFile($filePath, $relativePath);
                                }
                            }
                        }
                        
                        $zip->close();
                        
                        // 删除原始 SQL 文件，因为它已经被压缩到 ZIP 中
                        unlink($sql_backup_file);
                        $final_backup_file_path = $zip_backup_file; // 更新最终备份文件路径为 ZIP
                    } else {
                        // ZipArchive 无法打开，记录错误但继续使用 SQL 文件
                        error_log("ZipArchive 无法打开 ZIP 文件: " . $zip_backup_file);
                    }
                }
                
                $auth->logAction($auth->getCurrentUser()['id'], '创建数据备份', null, null, ['file' => basename($final_backup_file_path), 'size' => filesize($final_backup_file_path)]);
                set_flash_message('数据备份成功！文件名: ' . basename($final_backup_file_path), 'success');
                
            } catch (Exception $e) {
                set_flash_message('数据备份失败：' . $e->getMessage(), 'error');
            }
            safe_redirect(SITE_URL . '/admin/system/backup.php');
            break;
            
        case 'delete_backup':
            $auth->requirePermission('system.backup', '您没有权限删除备份文件。'); // 假设删除备份文件也需要 system.backup 权限
            
            $filename = sanitize_input($_POST['filename'] ?? '');
            if (empty($filename)) {
                set_flash_message('参数错误，文件名不能为空。', 'error');
                safe_redirect(SITE_URL . '/admin/system/backup.php');
            }
            
            $backup_file_path = ABSPATH . 'backups/' . basename($filename); // 使用 basename 确保路径安全
            if (file_exists($backup_file_path)) {
                if (unlink($backup_file_path)) {
                    $auth->logAction($auth->getCurrentUser()['id'], '删除数据备份', null, null, ['file' => basename($backup_file_path)]);
                    set_flash_message('备份文件删除成功！', 'success');
                } else {
                    set_flash_message('备份文件删除失败，请检查文件权限。', 'error');
                }
            } else {
                set_flash_message('备份文件不存在。', 'error');
            }
            safe_redirect(SITE_URL . '/admin/system/backup.php');
            break;
    }
}

// 处理文件下载请求
if (isset($_GET['download'])) {
    $filename = sanitize_input($_GET['download']);
    $backup_file_path = ABSPATH . 'backups/' . basename($filename); // 使用 basename 确保路径安全
    
    if (file_exists($backup_file_path)) {
        header('Content-Type: application/octet-stream'); // 通用二进制流
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"'); // 确保下载时的文件名
        header('Content-Length: ' . filesize($backup_file_path));
        header('X-Content-Type-Options: nosniff'); // 安全头
        readfile($backup_file_path);
        exit;
    } else {
        http_response_code(404);
        die("文件不存在或已损坏。");
    }
}

// 获取备份文件列表
$backup_dir = ABSPATH . 'backups/';
$backup_files = [];

if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        $full_path = $backup_dir . $file;
        if ($file !== '.' && $file !== '..' && is_file($full_path)) {
            $backup_files[] = [
                'name' => $file,
                'size' => filesize($full_path),
                'created' => filemtime($full_path)
            ];
        }
    }
    
    // 按创建时间倒序排序
    usort($backup_files, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// 辅助函数：生成数据库备份的 SQL 内容 (已移入 functions.php)
function generateDatabaseBackup($db_instance) { // 辅助函数：生成数据库备份的 SQL 内容
    $backup = "-- CMS Database Backup\n";
    $backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    // 获取所有表
    $tables_result = $db_instance->fetchAll("SHOW TABLES");
    
    foreach ($tables_result as $table) {
        $table_name = array_values($table)[0]; // 获取表名
        
        // 获取表结构
        $create_table = $db_instance->fetchOne("SHOW CREATE TABLE `{$table_name}`");
        $backup .= "\n-- Table structure for `{$table_name}`\n";
        $backup .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
        $backup .= $create_table['Create Table'] . ";\n\n";
        
        // 获取表数据
        $data = $db_instance->fetchAll("SELECT * FROM `{$table_name}`");
        if (!empty($data)) {
            $backup .= "-- Data for table `{$table_name}`\n";
            $backup .= "INSERT INTO `{$table_name}` VALUES\n";
            
            $rows = [];
            foreach ($data as $row) {
                $values = array_map(function($value) use ($db_instance) {
                    return $value === null ? 'NULL' : $db_instance->getConnection()->quote($value);
                }, array_values($row));
                $rows[] = '(' . implode(', ', $values) . ')';
            }
            
            $backup .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    return $backup;
}


// 获取并显示闪存消息
$flash_message = get_flash_message();

// 引入后台头部模板
include ABSPATH . 'templates/admin_header.php'; 
?>

<main class="content">
    <div class="page-header">
        <h1 class="page-title">数据备份</h1>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="createBackup()">
                <span class="icon">💾</span> 创建备份
            </button>
        </div>
    </div>
    
    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_message['type'] ?>">
            <i class="fas fa-<?= $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash_message['message']) ?>
        </div>
    <?php endif; ?>
    
    <div class="backup-info content-card mb-4">
        <div class="card-body">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">📊</div>
                    <div>
                        <h3>备份统计</h3>
                        <p>当前共有 <?= count($backup_files) ?> 个备份文件</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">🔄</div>
                    <div>
                        <h3>自动备份</h3>
                        <p>建议设置定时任务每天自动备份</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">⚠️</div>
                    <div>
                        <h3>注意事项</h3>
                        <p>请定期下载备份到本地保存</p>