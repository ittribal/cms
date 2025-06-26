<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

$auth = new Auth();

// 检查登录和权限
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!$auth->hasPermission('setting.edit')) {
    die('您没有权限访问此页面');
}

$db = Database::getInstance();
$message = '';
$error = '';

// 处理备份操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_backup':
            try {
                $backupResult = createDatabaseBackup();
                if ($backupResult['success']) {
                    $auth->logAction('创建数据库备份', null, null, null, ['filename' => $backupResult['filename']]);
                    $message = '备份创建成功：' . $backupResult['filename'];
                } else {
                    $error = '备份创建失败：' . $backupResult['error'];
                }
            } catch (Exception $e) {
                $error = '备份失败：' . $e->getMessage();
            }
            break;
            
        case 'restore_backup':
            $backupFile = $_POST['backup_file'] ?? '';
            if (empty($backupFile)) {
                $error = '请选择要恢复的备份文件';
            } else {
                try {
                    $restoreResult = restoreDatabaseBackup($backupFile);
                    if ($restoreResult['success']) {
                        $auth->logAction('恢复数据库备份', null, null, null, ['filename' => $backupFile]);
                        $message = '数据恢复成功';
                    } else {
                        $error = '数据恢复失败：' . $restoreResult['error'];
                    }
                } catch (Exception $e) {
                    $error = '恢复失败：' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_backup':
            $backupFile = $_POST['backup_file'] ?? '';
            if (!empty($backupFile)) {
                $backupPath = '../backups/' . $backupFile;
                if (file_exists($backupPath)) {
                    if (unlink($backupPath)) {
                        $auth->logAction('删除备份文件', null, null, null, ['filename' => $backupFile]);
                        $message = '备份文件删除成功';
                    } else {
                        $error = '备份文件删除失败';
                    }
                } else {
                    $error = '备份文件不存在';
                }
            }
            break;
    }
}

// 获取备份文件列表
$backupDir = '../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$backupFiles = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backupFiles[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'created' => filemtime($backupDir . $file)
            ];
        }
    }
    
    // 按创建时间排序
    usort($backupFiles, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// 创建数据库备份
function createDatabaseBackup() {
    try {
        $backupDir = '../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupPath = $backupDir . $filename;
        
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            $backupPath
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($backupPath)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            // 尝试PHP方式备份
            return createPHPBackup($backupPath, $filename);
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// PHP方式创建备份
function createPHPBackup($backupPath, $filename) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $backupContent = "-- MySQL Database Backup\n";
        $backupContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $backupContent .= "-- Database: " . DB_NAME . "\n\n";
        
        // 获取所有表
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            // 获取表结构
            $createTable = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            $backupContent .= "\n-- Table structure for `$table`\n";
            $backupContent .= "DROP TABLE IF EXISTS `$table`;\n";
            $backupContent .= $createTable['Create Table'] . ";\n\n";
            
            // 获取表数据
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $backupContent .= "-- Data for table `$table`\n";
                
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($db) {
                        return $value === null ? 'NULL' : $db->quote($value);
                    }, array_values($row));
                    
                    $backupContent .= "INSERT INTO `$table` ($columnList) VALUES (" . implode(', ', $values) . ");\n";
                }
                $backupContent .= "\n";
            }
        }
        
        if (file_put_contents($backupPath, $backupContent)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'error' => '无法写入备份文件'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 恢复数据库备份
function restoreDatabaseBackup($filename) {
    try {
        $backupPath = '../backups/' . $filename;
        
        if (!file_exists($backupPath)) {
            return ['success' => false, 'error' => '备份文件不存在'];
        }
        
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            $backupPath
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return ['success' => true];
        } else {
            // 尝试PHP方式恢复
            return restorePHPBackup($backupPath);
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// PHP方式恢复备份
function restorePHPBackup($backupPath) {
    try {
        $db = Database::getInstance()->getConnection();
        $sql = file_get_contents($backupPath);
        
        // 分割SQL语句
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                $db->exec($statement);
            }
        }
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据备份 - CMS后台</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include '../templates/admin_header.php'; ?>
    
    <div class="admin-container">
        <main class="main-content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>数据库备份管理</h1>
            </div>
            
            <!-- 备份操作 -->
            <div class="backup-section">
                <div class="backup-actions">
                    <h3>创建备份</h3>
                    <p>创建当前数据库的完整备份，包括所有表结构和数据。</p>
                    
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="action" value="create_backup">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('确定要创建数据库备份吗？')">
                            💾 创建备份
                        </button>
                    </form>
                    
                    <div class="backup-info">
                        <h4>⚠️ 重要提示:</h4>
                        <ul>
                            <li>备份可能需要一些时间，请耐心等待</li>
                            <li>定期备份数据以防止数据丢失</li>
                            <li>恢复操作将覆盖现有数据，请谨慎操作</li>
                            <li>建议在进行重要操作前先创建备份</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- 备份文件列表 -->
            <div class="backup-files">
                <h3>备份文件列表</h3>
                
                <?php if (empty($backupFiles)): ?>
                    <div class="no-backups">
                        <div class="no-backups-icon">📦</div>
                        <h4>暂无备份文件</h4>
                        <p>您还没有创建过任何备份文件。</p>
                    </div>
                <?php else: ?>
                    <div class="backup-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>文件名</th>
                                    <th>大小</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backupFiles as $backup): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($backup['name']); ?></strong>
                                    </td>
                                    <td><?php echo formatFileSize($backup['size']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $backup['created']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="../backups/<?php echo urlencode($backup['name']); ?>" 
                                               class="btn btn-sm btn-info" 
                                               download>📥 下载</a>
                                            
                                            <button onclick="restoreBackup('<?php echo htmlspecialchars($backup['name']); ?>')" 
                                                    class="btn btn-sm btn-warning">🔄 恢复</button>
                                            
                                            <button onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')" 
                                                    class="btn btn-sm btn-danger">🗑️ 删除</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 自动备份设置 -->
            <div class="auto-backup-section">
                <h3>自动备份设置</h3>
                <p>配置定期自动备份（需要配置cron任务）</p>
                
                <div class="cron-info">
                    <h4>Cron任务配置：</h4>
                    <code>0 2 * * * /usr/bin/php <?php echo realpath('.'); ?>/cron_backup.php</code>
                    <p><small>每天凌晨2点自动创建备份</small></p>
                </div>
            </div>
        </main>
    </div>
    
    <!-- 隐藏的表单 -->
    <form id="restoreForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="restore_backup">
        <input type="hidden" name="backup_file" id="restoreFile">
    </form>
    
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_backup">
        <input type="hidden" name="backup_file" id="deleteFile">
    </form>
    
    <script>
        function restoreBackup(filename) {
            if (confirm('⚠️ 警告：恢复备份将覆盖当前所有数据！\n\n确定要恢复备份 "' + filename + '" 吗？\n\n此操作不可撤销！')) {
                if (confirm('请再次确认：您确定要继续恢复操作吗？')) {
                    document.getElementById('restoreFile').value = filename;
                    document.getElementById('restoreForm').submit();
                }
            }
        }
        
        function deleteBackup(filename) {
            if (confirm('确定要删除备份文件 "' + filename + '" 吗？\n\n此操作不可撤销！')) {
                document.getElementById('deleteFile').value = filename;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
    
    <style>
        .backup-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .backup-actions h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .backup-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        
        .backup-info h4 {
            color: #856404;
            margin-bottom: 1rem;
        }
        
        .backup-info ul {
            color: #856404;
            margin-left: 1rem;
        }
        
        .backup-info li {
            margin-bottom: 0.5rem;
        }
        
        .backup-files {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .backup-files h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .no-backups {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .no-backups-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .backup-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .backup-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
        }
        
        .backup-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .backup-table tr:hover {
            background: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .auto-backup-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .auto-backup-section h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .cron-info {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: #e7f3ff;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .cron-info h4 {
            color: #004085;
            margin-bottom: 1rem;
        }
        
        .cron-info code {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            display: block;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
    </style>
</body>
</html>