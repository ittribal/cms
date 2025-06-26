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

if (!$auth->hasPermission('media.view')) {
    die('您没有权限访问此页面');
}

$pageTitle = '媒体库管理';
$currentUser = $auth->getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// 处理上传
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'upload':
            $result = handleFileUpload($_FILES['files'] ?? []);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'delete':
            $result = deleteMediaFile($_POST['id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'batch_delete':
            $result = batchDeleteMedia($_POST['ids']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'update_info':
            $result = updateMediaInfo($_POST);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// 处理文件上传
function handleFileUpload($files) {
    global $db, $auth;
    
    try {
        if (empty($files['name'][0])) {
            return ['success' => false, 'message' => '请选择要上传的文件'];
        }
        
        $uploadDir = '../uploads/media/' . date('Y/m/');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        $uploadedCount = 0;
        $errors = [];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "文件 {$files['name'][$i]} 上传失败";
                continue;
            }
            
            $originalName = $files['name'][$i];
            $tmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $mimeType = mime_content_type($tmpName);
            
            // 验证文件类型
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = "文件 {$originalName} 类型不支持";
                continue;
            }
            
            // 验证文件大小
            if ($fileSize > $maxSize) {
                $errors[] = "文件 {$originalName} 超过大小限制";
                continue;
            }
            
            // 生成唯一文件名
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            if (move_uploaded_file($tmpName, $filePath)) {
                // 保存到数据库
                $relativePath = 'uploads/media/' . date('Y/m/') . $filename;
                
                $sql = "INSERT INTO media_files (filename, original_name, file_path, file_size, mime_type, uploaded_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                if ($db->execute($sql, [$filename, $originalName, $relativePath, $fileSize, $mimeType, $auth->getCurrentUser()['id']])) {
                    $uploadedCount++;
                    $auth->logAction('上传文件', "文件: {$originalName}");
                } else {
                    unlink($filePath);
                    $errors[] = "文件 {$originalName} 数据库保存失败";
                }
            } else {
                $errors[] = "文件 {$originalName} 保存失败";
            }
        }
        
        if ($uploadedCount > 0) {
            $message = "成功上传 {$uploadedCount} 个文件";
            if (!empty($errors)) {
                $message .= "，但有 " . count($errors) . " 个文件失败";
            }
            return ['success' => true, 'message' => $message];
        } else {
            return ['success' => false, 'message' => '所有文件上传失败：' . implode(', ', $errors)];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '上传失败：' . $e->getMessage()];
    }
}

// 删除媒体文件
function deleteMediaFile($id) {
    global $db, $auth;
    
    try {
        if (!$auth->hasPermission('media.delete')) {
            return ['success' => false, 'message' => '没有删除权限'];
        }
        
        $media = $db->fetchOne("SELECT * FROM media_files WHERE id = ?", [$id]);
        if (!$media) {
            return ['success' => false, 'message' => '文件不存在'];
        }
        
        // 检查文件是否被使用
        $usage = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE featured_image = ?", [$media['file_path']]);
        if ($usage['count'] > 0) {
            return ['success' => false, 'message' => '文件正在被使用，无法删除'];
        }
        
        // 删除物理文件
        if (file_exists('../' . $media['file_path'])) {
            unlink('../' . $media['file_path']);
        }
        
        // 删除数据库记录
        if ($db->execute("DELETE FROM media_files WHERE id = ?", [$id])) {
            $auth->logAction('删除文件', "文件: {$media['original_name']}");
            return ['success' => true, 'message' => '文件删除成功'];
        }
        
        return ['success' => false, 'message' => '删除失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '删除失败：' . $e->getMessage()];
    }
}

// 批量删除
function batchDeleteMedia($ids) {
    global $db, $auth;
    
    try {
        if (empty($ids)) {
            return ['success' => false, 'message' => '请选择要删除的文件'];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $medias = $db->fetchAll("SELECT * FROM media_files WHERE id IN ({$placeholders})", $ids);
        
        $deletedCount = 0;
        foreach ($medias as $media) {
            $result = deleteMediaFile($media['id']);
            if ($result['success']) {
                $deletedCount++;
            }
        }
        
        return ['success' => true, 'message' => "成功删除 {$deletedCount} 个文件"];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '批量删除失败：' . $e->getMessage()];
    }
}

// 更新媒体信息
function updateMediaInfo($data) {
    global $db, $auth;
    
    try {
        $sql = "UPDATE media_files SET alt_text = ?, caption = ? WHERE id = ?";
        if ($db->execute($sql, [$data['alt_text'], $data['caption'], $data['id']])) {
            $auth->logAction('更新文件信息', "文件ID: {$data['id']}");
            return ['success' => true, 'message' => '文件信息更新成功'];
        }
        
        return ['success' => false, 'message' => '更新失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '更新失败：' . $e->getMessage()];
    }
}

include '../templates/admin_header.php';
?>

<link rel="stylesheet" href="css/media.css">

<div class="media-page">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-images"></i> 媒体库管理</h1>
                <p>管理网站图片、文档等媒体文件</p>
            </div>
            <div class="header-actions">
                <button onclick="showUploadModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 上传文件
                </button>
                <button onclick="showCreateFolderModal()" class="btn btn-info">
                    <i class="fas fa-folder-plus"></i> 新建文件夹
                </button>
            </div>
        </div>

        <!-- 统计信息 -->
        <div class="stats-grid">
            <?php
            $stats = [
                'total' => $db->fetchOne("SELECT COUNT(*) as count FROM media_files")['count'],
                'images' => $db->fetchOne("SELECT COUNT(*) as count FROM media_files WHERE mime_type LIKE 'image/%'")['count'],
                'documents' => $db->fetchOne("SELECT COUNT(*) as count FROM media_files WHERE mime_type NOT LIKE 'image/%'")['count'],
                'size' => $db->fetchOne("SELECT SUM(file_size) as size FROM media_files")['size'] ?? 0
            ];
            ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: #3498db;">
                    <i class="fas fa-file"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">总文件数</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #27ae60;">
                    <i class="fas fa-image"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['images']); ?></div>
                    <div class="stat-label">图片文件</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #e74c3c;">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($stats['documents']); ?></div>
                    <div class="stat-label">文档文件</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f39c12;">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo formatFileSize($stats['size']); ?></div>
                    <div class="stat-label">存储空间</div>
                </div>
            </div>
        </div>

        <!-- 文件筛选 -->
        <div class="filter-section">
            <div class="filter-controls">
                <div class="view-controls">
                    <button class="view-btn active" data-view="grid" title="网格视图">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-btn" data-view="list" title="列表视图">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
                
                <div class="filter-controls">
                    <select id="typeFilter" class="form-control">
                        <option value="">所有类型</option>
                        <option value="image">图片</option>
                        <option value="document">文档</option>
                    </select>
                    
                    <input type="text" id="searchBox" class="form-control" placeholder="搜索文件名...">
                    
                    <button onclick="refreshMediaList()" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 批量操作 -->
        <div class="batch-actions" style="display: none;">
            <div class="batch-content">
                <span>已选择 <strong class="selected-count">0</strong> 个文件</span>
                <div class="batch-buttons">
                    <button onclick="batchDownload()" class="btn btn-sm btn-info">批量下载</button>
                    <button onclick="batchDelete()" class="btn btn-sm btn-danger">批量删除</button>
                    <button onclick="clearSelection()" class="btn btn-sm btn-secondary">取消选择</button>
                </div>
            </div>
        </div>

        <!-- 媒体文件网格 -->
        <div class="content-card">
            <div class="card-header">
                <h3>媒体文件</h3>
                <div class="card-actions">
                    <span class="file-count">共 <strong id="fileCount">0</strong> 个文件</span>
                </div>
            </div>
            
            <div class="media-container" id="mediaContainer">
                <div class="loading-spinner" id="loadingSpinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>加载中...</span>
                </div>
            </div>
            
            <!-- 分页 -->
            <div class="pagination-wrapper" id="paginationWrapper" style="display: none;">
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </main>

    <!-- 上传模态框 -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-upload"></i> 上传文件</h3>
                <button type="button" class="close" onclick="closeModal('uploadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            <p>拖拽文件到此处或点击选择文件</p>
                            <small>支持图片、PDF、文本文件，单个文件最大 10MB</small>
                        </div>
                        <input type="file" id="fileInput" name="files[]" multiple 
                               accept="image/*,.pdf,.txt,.doc,.docx" style="display: none;">
                    </div>
                    
                    <div class="file-preview" id="filePreview"></div>
                    
                    <div class="upload-progress" id="uploadProgress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill">0%</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> 开始上传
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('uploadModal')">
                            取消
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 文件信息模态框 -->
    <div id="fileInfoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> 文件信息</h3>
                <button type="button" class="close" onclick="closeModal('fileInfoModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="fileInfoContent"></div>
            </div>
        </div>
    </div>
</div>

<script src="js/media.js"></script>

<?php
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

include '../templates/admin_footer.php';
?>