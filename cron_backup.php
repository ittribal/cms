#!/usr/bin/env php
<?php
/**
 * 自动备份脚本
 * 使用方法：添加到cron任务中
 * 0 2 * * * /usr/bin/php /path/to/cron_backup.php
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';

// 创建备份目录
$backupDir = __DIR__ . '/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// 生成备份文件名
$filename = 'auto_backup_' . date('Y-m-d_H-i-s') . '.sql';
$backupPath = $backupDir . $filename;

try {
    // 尝试使用mysqldump
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        $backupPath
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0 || !file_exists($backupPath)) {
        // 使用PHP方式备份
        createPHPBackup($backupPath);
    }
    
    // 检查备份是否成功
    if (file_exists($backupPath) && filesize($backupPath) > 0) {
        echo "备份成功创建: {$filename}\n";
        
        // 清理旧备份（保留最近30个）
        cleanOldBackups($backupDir, 30);
        
        // 记录到日志
        $logMessage = date('Y-m-d H:i:s') . " - 自动备份成功: {$filename}\n";
        file_put_contents(__DIR__ . '/logs/backup.log', $logMessage, FILE_APPEND | LOCK_EX);
        
    } else {
        throw new Exception('备份文件创建失败');
    }
    
} catch (Exception $e) {
    $errorMessage = date('Y-m-d H:i:s') . " - 备份失败: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/logs/backup_error.log', $errorMessage, FILE_APPEND | LOCK_EX);
    echo "备份失败: " . $e->getMessage() . "\n";
    exit(1);
}

function createPHPBackup($backupPath) {
    $db = Database::getInstance()->getConnection();
    
    $backupContent = "-- MySQL Database Backup (Auto)\n";
    $backupContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $backupContent .= "-- Database: " . DB_NAME . "\n\n";
    
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $createTable = $db->query("SHOW CREATE TABLE `$table`")->fetch();
        $backupContent .= "\n-- Table structure for `$table`\n";
        $backupContent .= "DROP TABLE IF EXISTS `$table`;\n";
        $backupContent .= $createTable['Create Table'] . ";\n\n";
        
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
    
    if (!file_put_contents($backupPath, $backupContent)) {
        throw new Exception('无法写入备份文件');
    }
}

function cleanOldBackups($backupDir, $keepCount) {
    $files = glob($backupDir . '*.sql');
    
    // 按修改时间排序
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // 删除多余的备份文件
    $filesToDelete = array_slice($files, $keepCount);
    foreach ($filesToDelete as $file) {
        if (unlink($file)) {
            echo "删除旧备份: " . basename($file) . "\n";
        }
    }
}
?>
3. 缓存管理系统 (admin/cache.php)
php
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

$message = '';
$error = '';

// 缓存目录配置
$cacheConfig = [
    'page_cache' => '../cache/pages/',
    'template_cache' => '../cache/templates/',
    'data_cache' => '../cache/data/',
    'image_cache' => '../cache/images/',
];

// 处理缓存操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'clear_all':
            $clearedCount = 0;
            foreach ($cacheConfig as $name => $path) {
                $clearedCount += clearCacheDirectory($path);
            }
            $auth->logAction('清理所有缓存', null, null, null, ['cleared_count' => $clearedCount]);
            $message = "成功清理 {$clearedCount} 个缓存文件";
            break;
            
        case 'clear_specific':
            $cacheType = $_POST['cache_type'] ?? '';
            if (isset($cacheConfig[$cacheType])) {
                $clearedCount = clearCacheDirectory($cacheConfig[$cacheType]);
                $auth->logAction('清理缓存', null, null, null, ['type' => $cacheType, 'cleared_count' => $clearedCount]);
                $message = "成功清理 {$clearedCount} 个 {$cacheType} 缓存文件";
            } else {
                $error = '无效的缓存类型';
            }
            break;
            
        case 'optimize_database':
            $optimizeResult = optimizeDatabase();
            if ($optimizeResult['success']) {
                $auth->logAction('优化数据库', null, null, null, $optimizeResult);
                $message = '数据库优化完成';
            } else {
                $error = '数据库优化失败：' . $optimizeResult['error'];
            }
            break;
    }
}

// 获取缓存统计信息
$cacheStats = [];
foreach ($cacheConfig as $name => $path) {
    $stats = getCacheDirectoryStats($path);
    $cacheStats[$name] = [
        'name' => $name,
        'path' => $path,
        'file_count' => $stats['file_count'],
        'total_size' => $stats['total_size'],
        'last_modified' => $stats['last_modified']
    ];
}

// 获取系统缓存信息
$systemStats = [
    'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status(),
    'apcu_enabled' => function_exists('apcu_cache_info'),
    'memory_usage' => memory_get_usage(true),
    'memory_peak' => memory_get_peak_usage(true),
];

// 清理缓存目录
function clearCacheDirectory($path) {
    $clearedCount = 0;
    
    if (!is_dir($path)) {
        return $clearedCount;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            if (unlink($file->getPathname())) {
                $clearedCount++;
            }
        } elseif ($file->isDir()) {
            rmdir($file->getPathname());
        }
    }
    
    return $clearedCount;
}

// 获取缓存目录统计信息
function getCacheDirectoryStats($path) {
    $stats = [
        'file_count' => 0,
        'total_size' => 0,
        'last_modified' => 0
    ];
    
    if (!is_dir($path)) {
        return $stats;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $stats['file_count']++;
            $stats['total_size'] += $file->getSize();
            $stats['last_modified'] = max($stats['last_modified'], $file->getMTime());
        }
    }
    
    return $stats;
}

// 优化数据库
function optimizeDatabase() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // 获取所有表
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $optimizedTables = [];
        foreach ($tables as $table) {
            $result = $db->query("OPTIMIZE TABLE `$table`")->fetch();
            $optimizedTables[] = [
                'table' => $table,
                'status' => $result['Msg_text'] ?? 'OK'
            ];
        }
        
        return [
            'success' => true,
            'optimized_tables' => $optimizedTables
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>缓存管理 - CMS后台</title>
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
                <h1>缓存管理</h1>
                <div class="header-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="clear_all">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('确定要清理所有缓存吗？')">
                            🗑️ 清理所有缓存
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- 缓存统计 -->
            <div class="cache-stats">
                <h3>缓存统计</h3>
                <div class="stats-grid">
                    <?php foreach ($cacheStats as $stats): ?>
                        <div class="stat-card">
                            <div class="stat-header">
                                <h4><?php echo ucfirst(str_replace('_', ' ', $stats['name'])); ?></h4>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="clear_specific">
                                    <input type="hidden" name="cache_type" value="<?php echo $stats['name']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('确定要清理此缓存吗？')">
                                        清理
                                    </button>
                                </form>
                            </div>
                            <div class="stat-body">
                                <div class="stat-item">
                                    <span class="stat-label">文件数量:</span>
                                    <span class="stat-value"><?php echo number_format($stats['file_count']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">总大小:</span>
                                    <span class="stat-value"><?php echo formatBytes($stats['total_size']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">最后修改:</span>
                                    <span class="stat-value">
                                        <?php echo $stats['last_modified'] ? date('Y-m-d H:i', $stats['last_modified']) : '无'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 系统缓存信息 -->
            <div class="system-cache">
                <h3>系统缓存信息</h3>
                <div class="system-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">OPcache 状态:</span>
                            <span class="info-value <?php echo $systemStats['opcache_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                                <?php echo $systemStats['opcache_enabled'] ? '已启用' : '未启用'; ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">APCu 状态:</span>
                            <span class="info-value <?php echo $systemStats['apcu_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                                <?php echo $systemStats['apcu_enabled'] ? '已启用' : '未启用'; ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">内存使用:</span>
                            <span class="info-value"><?php echo formatBytes($systemStats['memory_usage']); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">内存峰值:</span>
                            <span class="info-value"><?php echo formatBytes($systemStats['memory_peak']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 数据库优化 -->
            <div class="database-optimization">
                <h3>数据库优化</h3>
                <p>优化数据库表可以提高查询性能和回收空间。</p>
                
                <form method="POST" class="optimization-form">
                    <input type="hidden" name="action" value="optimize_database">
                    <button type="submit" class="btn btn-info" onclick="return confirm('确定要优化数据库吗？此操作可能需要一些时间。')">
                        ⚡ 优化数据库
                    </button>
                </form>
                
                <div class="optimization-info">
                    <h4>优化说明:</h4>
                    <ul>
                        <li>清理表碎片，提高查询效率</li>
                        <li>回收删除记录占用的空间</li>
                        <li>重建索引，优化查询性能</li>
                        <li>建议在访问量较低时进行优化</li>
                    </ul>
                </div>
            </div>
            
            <!-- 缓存配置建议 -->
            <div class="cache-recommendations">
                <h3>性能优化建议</h3>
                <div class="recommendations-grid">
                    <div class="recommendation-item">
                        <h4>🚀 启用 OPcache</h4>
                        <p>在 php.ini 中启用 OPcache 可以显著提升 PHP 性能</p>
                        <code>opcache.enable=1<br>opcache.memory_consumption=128</code>
                    </div>
                    
                    <div class="recommendation-item">
                        <h4>💾 配置 APCu</h4>
                        <p>APCu 提供用户缓存功能，适合缓存配置和查询结果</p>
                        <code>apc.enabled=1<br>apc.shm_size=64M</code>
                    </div>
                    
                    <div class="recommendation-item">
                        <h4>🗂️ 文件缓存</h4>
                        <p>将频繁访问的数据缓存到文件中，减少数据库查询</p>
                        <code>定期清理过期缓存<br>使用合适的缓存策略</code>
                    </div>
                    
                    <div class="recommendation-item">
                        <h4>🔄 CDN 加速</h4>
                        <p>使用 CDN 缓存静态资源，提升页面加载速度</p>
                        <code>图片、CSS、JS 文件<br>启用 Gzip 压缩</code>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .cache-stats {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cache-stats h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .stat-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .stat-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
        }
        
        .stat-header h4 {
            margin: 0;
            color: #495057;
        }
        
        .stat-body {
            padding: 1.5rem;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .stat-item:last-child {
            margin-bottom: 0;
        }
        
        .stat-label {
            color: #6c757d;
        }
        
        .stat-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .system-cache {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .system-cache h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-label {
            font-weight: 500;
            color: #495057;
        }
        
        .info-value {
            font-weight: 600;
        }
        
        .status-enabled {
            color: #28a745;
        }
        
        .status-disabled {
            color: #dc3545;
        }
        
        .database-optimization {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .database-optimization h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .optimization-form {
            margin: 1.5rem 0;
        }
        
        .optimization-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #e3f2fd;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
        }
        
        .optimization-info h4 {
            color: #1565c0;
            margin-bottom: 1rem;
        }
        
        .optimization-info ul {
            color: #1565c0;
            margin-left: 1rem;
        }
        
        .optimization-info li {
            margin-bottom: 0.5rem;
        }
        
        .cache-recommendations {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cache-recommendations h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .recommendation-item {
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .recommendation-item h4 {
            color: #495057;
            margin-bottom: 1rem;
        }
        
        .recommendation-item p {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .recommendation-item code {
            display: block;
            background: #e9ecef;
            padding: 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #6c757d;
            color: #6c757d;
        }
        
        .btn-outline:hover {
            background: #6c757d;
            color: white;
        }
        
        .header-actions {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</body>
</html>