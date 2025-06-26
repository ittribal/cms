<?php
// ==================== admin/content/media.php - 媒体管理 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('media.view');

// 处理文件上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $auth->requirePermission('media.upload');
    
    if (!verify_csrf_token($_POST['csrf_token'])) {
        json_response(['success' => false, 'message' => 'CSRF验证失败'], 400);
    }
    
    if (!isset($_FILES['files'])) {
        json_response(['success' => false, 'message' => '没有选择文件'], 400);
    }
    
    $uploaded_files = [];
    $errors = [];
    
    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            try {
                $file_info = [
                    'name' => $_FILES['files']['name'][$key],
                    'tmp_name' => $tmp_name,
                    'size' => $_FILES['files']['size'][$key],
                    'type' => $_FILES['files']['type'][$key],
                    'error' => $_FILES['files']['error'][$key]
                ];
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $upload_result = handle_file_upload($file_info, 'uploads/media/', $allowed_types);
                
                // 保存到数据库
                $media_id = $db->insert('media_files', [
                    'filename' => $upload_result['filename'],
                    'original_name' => $upload_result['original_name'],
                    'file_path' => $upload_result['filepath'],
                    'file_size' => $upload_result['size'],
                    'mime_type' => $upload_result['type'],
                    'uploaded_by' => $_SESSION['user_id'],
                    'alt_text' => '',
                    'caption' => ''
                ]);
                
                $uploaded_files[] = [
                    'id' => $media_id,
                    'filename' => $upload_result['filename'],
                    'original_name' => $upload_result['original_name'],
                    'file_path' => $upload_result['filepath'],
                    'file_size' => $upload_result['size']
                ];
                
                $auth->logAction($_SESSION['user_id'], 'media_upload', 'media_files', $media_id);
                
            } catch (Exception $e) {
                $errors[] = $file_info['name'] . ': ' . $e->getMessage();
            }
        }
    }
    
    json_response([
        'success' => !empty($uploaded_files),
        'uploaded' => $uploaded_files,
        'errors' => $errors,
        'message' => count($uploaded_files) . ' 个文件上传成功'
    ]);
}

// 处理文件删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $auth->requirePermission('media.delete');
    
    if (!verify_csrf_token($_POST['csrf_token'])) {
        json_response(['success' => false, 'message' => 'CSRF验证失败'], 400);
    }
    
    $file_id = intval($_POST['file_id'] ?? 0);
    if (!$file_id) {
        json_response(['success' => false, 'message' => '参数错误'], 400);
    }
    
    try {
        $file = $db->fetchOne("SELECT * FROM media_files WHERE id = ?", [$file_id]);
        if ($file) {
            // 删除物理文件
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            
            // 从数据库删除记录
            $db->delete('media_files', 'id = ?', [$file_id]);
            
            $auth->logAction($_SESSION['user_id'], 'media_delete', 'media_files', $file_id);
            
            json_response(['success' => true, 'message' => '文件删除成功']);
        } else {
            json_response(['success' => false, 'message' => '文件不存在'], 404);
        }
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => '删除失败: ' . $e->getMessage()], 500);
    }
}

// 分页和筛选
$page = intval($_GET['page'] ?? 1);
$per_page = 24;
$search = sanitize_input($_GET['search'] ?? '');
$type_filter = $_GET['type'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = '(original_name LIKE ? OR alt_text LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($type_filter)) {
    $where_conditions[] = 'mime_type LIKE ?';
    $params[] = "$type_filter%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$total = $db->fetchOne("SELECT COUNT(*) as count FROM media_files $where_clause", $params)['count'];
$pagination = paginate($total, $page, $per_page);

$media_files = $db->fetchAll(
    "SELECT m.*, u.username as uploader_name 
     FROM media_files m 
     LEFT JOIN admin_users u ON m.uploaded_by = u.id 
     $where_clause
     ORDER BY m.created_at DESC 
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>媒体管理 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">媒体管理</h1>
                <div class="page-actions">
                    <?php if ($auth->hasPermission('media.upload')): ?>
                        <button class="btn btn-primary" onclick="openUploadModal()">
                            <span class="icon">📁</span> 上传文件
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="filters">
                    <form method="GET" action="" class="filter-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="搜索文件名..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <select name="type">
                                <option value="">所有类型</option>
                                <option value="image" <?= $type_filter === 'image' ? 'selected' : '' ?>>图片</option>
                                <option value="application" <?= $type_filter === 'application' ? 'selected' : '' ?>>文档</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">筛选</button>
                        <a href="media.php" class="btn btn-outline">清除</a>
                    </form>
                </div>
                
                <?php if (!empty($media_files)): ?>
                    <div class="media-grid">
                        <?php foreach ($media_files as $file): ?>
                            <div class="media-item" data-file-id="<?= $file['id'] ?>">
                                <div class="media-preview">
                                    <?php if (strpos($file['mime_type'], 'image/') === 0): ?>
                                        <img src="/<?= htmlspecialchars($file['file_path']) ?>" 
                                             alt="<?= htmlspecialchars($file['alt_text'] ?: $file['original_name']) ?>"
                                             onclick="openPreviewModal(<?= htmlspecialchars(json_encode($file)) ?>)">
                                    <?php else: ?>
                                        <div class="file-icon" onclick="openPreviewModal(<?= htmlspecialchars(json_encode($file)) ?>)">
                                            📄
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="media-info">
                                    <div class="media-title" title="<?= htmlspecialchars($file['original_name']) ?>">
                                        <?= htmlspecialchars(mb_substr($file['original_name'], 0, 20)) ?><?= mb_strlen($file['original_name']) > 20 ? '...' : '' ?>
                                    </div>
                                    <div class="media-meta">
                                        <span><?= format_file_size($file['file_size']) ?></span>
                                        <span><?= date('Y-m-d', strtotime($file['created_at'])) ?></span>
                                    </div>
                                    <div class="media-actions">
                                        <button class="btn btn-sm btn-outline" onclick="copyUrl('<?= htmlspecialchars($file['file_path']) ?>')">
                                            复制链接
                                        </button>
                                        <?php if ($auth->hasPermission('media.delete')): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars($file['original_name']) ?>')">
                                                删除
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="pagination">
                            <?php if ($pagination['has_prev']): ?>
                                <a href="?page=<?= $pagination['prev_page'] ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type_filter) ?>">上一页</a>
                            <?php endif; ?>
                            
                            <span class="page-info">
                                第 <?= $pagination['current_page'] ?> 页，共 <?= $pagination['total_pages'] ?> 页 (总计 <?= $total ?> 个文件)
                            </span>
                            
                            <?php if ($pagination['has_next']): ?>
                                <a href="?page=<?= $pagination['next_page'] ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type_filter) ?>">下一页</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📁</div>
                        <h3>暂无媒体文件</h3>
                        <p>还没有上传任何文件，现在就开始上传吧！</p>
                        <?php if ($auth->hasPermission('media.upload')): ?>
                            <button class="btn btn-primary" onclick="openUploadModal()">上传第一个文件</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- 上传模态框 -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>上传文件</h3>
                <button class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">📁</div>
                        <div class="upload-text">
                            <p>拖拽文件到此处或<span class="upload-link">点击选择文件</span></p>
                            <small>支持 JPG, PNG, GIF, WebP 格式，单个文件最大 32MB</small>
                        </div>
                        <input type="file" id="fileInput" name="files[]" multiple accept="image/*" style="display: none;">
                    </div>
                    
                    <div id="uploadProgress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="progress-text" id="progressText">0%</div>
                    </div>
                    
                    <div id="uploadResults"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeUploadModal()">关闭</button>
                <button type="button" class="btn btn-primary" onclick="startUpload()" id="uploadBtn">开始上传</button>
            </div>
        </div>
    </div>
    
    <!-- 文件预览模态框 -->
    <div id="previewModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="previewTitle">文件详情</h3>
                <button class="modal-close" onclick="closePreviewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="previewContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closePreviewModal()">关闭</button>
            </div>
        </div>
    </div>
    
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        .media-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .media-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .media-preview {
            aspect-ratio: 1;
            overflow: hidden;
            cursor: pointer;
        }
        
        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .media-preview img:hover {
            transform: scale(1.05);
        }
        
        .file-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            background: #f8fafc;
            cursor: pointer;
        }
        
        .media-info {
            padding: 1rem;
        }
        
        .media-title {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        
        .media-meta {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }
        
        .media-meta span {
            margin-right: 0.5rem;
        }
        
        .media-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            background: #f8fafc;
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background: #e0e7ff;
        }
        
        .upload-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .upload-link {
            color: #667eea;
            text-decoration: underline;
            cursor: pointer;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .progress-text {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }
        
        .modal-large .modal-content {
            max-width: 800px;
        }
        
        .preview-image {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
        }
        
        .file-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #6b7280;
        }
        
        .detail-value {
            color: #374151;
        }
    </style>
    
    <script>
        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
            document.getElementById('uploadForm').reset();
            document.getElementById('uploadResults').innerHTML = '';
            document.getElementById('uploadProgress').style.display = 'none';
        }
        
        function openPreviewModal(file) {
            document.getElementById('previewTitle').textContent = file.original_name;
            
            let content = '';
            if (file.mime_type.startsWith('image/')) {
                content = `<img src="/${file.file_path}" class="preview-image" alt="${file.original_name}">`;
            } else {
                content = `<div class="file-icon" style="font-size: 6rem;">📄</div>`;
            }
            
            content += `
                <div class="file-details">
                    <div class="detail-item">
                        <span class="detail-label">文件名:</span>
                        <span class="detail-value">${file.original_name}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">文件大小:</span>
                        <span class="detail-value">${formatFileSize(file.file_size)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">文件类型:</span>
                        <span class="detail-value">${file.mime_type}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">上传时间:</span>
                        <span class="detail-value">${file.created_at}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">上传者:</span>
                        <span class="detail-value">${file.uploader_name || '未知'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">文件URL:</span>
                        <span class="detail-value">
                            <input type="text" value="/${file.file_path}" readonly style="width: 100%; padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                        </span>
                    </div>
                </div>
            `;
            
            document.getElementById('previewContent').innerHTML = content;
            document.getElementById('previewModal').style.display = 'block';
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
        }
        
        function formatFileSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;
            
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }
            
            return Math.round(size * 100) / 100 + ' ' + units[unitIndex];
        }
        
        function copyUrl(path) {
            const url = window.location.origin + '/' + path;
            navigator.clipboard.writeText(url).then(() => {
                showMessage('链接已复制到剪贴板', 'success');
            });
        }
        
        function deleteFile(fileId, fileName) {
            if (!confirm(`确定要删除文件"${fileName}"吗？`)) {
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'delete',
                    'file_id': fileId,
                    'csrf_token': '<?= generate_csrf_token() ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    // 移除文件元素
                    document.querySelector(`[data-file-id="${fileId}"]`).remove();
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('删除失败', 'error');
            });
        }
        
        function showMessage(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; animation: fadeInRight 0.3s ease;';
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.animation = 'fadeOutRight 0.3s ease';
                setTimeout(() => alertDiv.remove(), 300);
            }, 3000);
        }
        
        // 上传功能
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
        });
        
        function startUpload() {
            const files = fileInput.files;
            if (files.length === 0) {
                alert('请选择要上传的文件');
                return;
            }
            
            const formData = new FormData(document.getElementById('uploadForm'));
            
            document.getElementById('uploadProgress').style.display = 'block';
            document.getElementById('uploadBtn').disabled = true;
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentage = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('progressFill').style.width = percentage + '%';
                    document.getElementById('progressText').textContent = percentage + '%';
                }
            });
            
            xhr.addEventListener('load', () => {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showMessage(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(response.message, 'error');
                }
                
                document.getElementById('uploadBtn').disabled = false;
            });
            
            xhr.addEventListener('error', () => {
                showMessage('上传失败', 'error');
                document.getElementById('uploadBtn').disabled = false;
            });
            
            xhr.open('POST', '');
            xhr.send(formData);
        }
        
        // 点击模态框外部关闭
        window.onclick = function(event) {
            const uploadModal = document.getElementById('uploadModal');
            const previewModal = document.getElementById('previewModal');
            
            if (event.target === uploadModal) {
                closeUploadModal();
            }
            if (event.target === previewModal) {
                closePreviewModal();
            }
        }
    </script>
</body>
</html>