<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';

$auth = new Auth();

// 检查登录状态
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => '未登录']);
    exit;
}

// 处理文件上传
if ($_FILES && isset($_FILES['upload'])) {
    $file = $_FILES['upload'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // 验证文件类型
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => '不支持的文件类型']);
        exit;
    }
    
    // 验证文件大小
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => '文件大小超过限制']);
        exit;
    }
    
    // 生成文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = date('Y/m/') . uniqid() . '.' . $extension;
    $uploadDir = UPLOAD_PATH . dirname($fileName);
    $uploadPath = UPLOAD_PATH . $fileName;
    
    // 创建目录
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 移动文件
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // 记录上传日志
        $auth->logAction('上传文件', null, null, null, ['filename' => $fileName]);
        
        echo json_encode([
            'uploaded' => true,
            'fileName' => basename($fileName),
            'url' => UPLOAD_URL . $fileName
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => '文件上传失败']);
    }
    exit;
}

// 获取文件列表
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $files = [];
    $uploadDir = UPLOAD_PATH;
    
    function scanDirectory($dir, $baseDir, &$files) {
        if (!is_dir($dir)) return;
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $fullPath = $dir . '/' . $item;
            $relativePath = str_replace($baseDir . '/', '', $fullPath);
            
            if (is_dir($fullPath)) {
                scanDirectory($fullPath, $baseDir, $files);
            } else {
                $files[] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'url' => UPLOAD_URL . $relativePath,
                    'size' => filesize($fullPath),
                    'type' => mime_content_type($fullPath),
                    'modified' => filemtime($fullPath)
                ];
            }
        }
    }
    
    scanDirectory($uploadDir, $uploadDir, $files);
    
    // 按修改时间排序
    usort($files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    header('Content-Type: application/json');
    echo json_encode($files);
    exit;
}

if ($action === 'delete' && isset($_POST['file'])) {
    $file = $_POST['file'];
    $filePath = UPLOAD_PATH . $file;
    
    if (file_exists($filePath) && unlink($filePath)) {
        $auth->logAction('删除文件', null, null, null, ['filename' => $file]);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => '文件不存在或删除失败']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件管理 - CMS后台</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include '../templates/admin_header.php'; ?>
    
    <div class="admin-container">
        <main class="main-content">
            <div class="page-header">
                <h1>文件管理</h1>
                <button onclick="openUploadModal()" class="btn btn-primary">📤 上传文件</button>
            </div>
            
            <!-- 文件统计 -->
            <div class="file-stats">
                <div class="stat-item">
                    <span class="stat-label">总文件数:</span>
                    <span class="stat-value" id="totalFiles">-</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">总大小:</span>
                    <span class="stat-value" id="totalSize">-</span>
                </div>
            </div>
            
            <!-- 文件筛选 -->
            <div class="file-filters">
                <input type="text" id="searchFiles" placeholder="搜索文件..." class="form-control">
                <select id="typeFilter" class="form-control">
                    <option value="">所有类型</option>
                    <option value="image">图片</option>
                    <option value="document">文档</option>
                    <option value="video">视频</option>
                    <option value="audio">音频</option>
                </select>
                <button onclick="refreshFiles()" class="btn btn-secondary">刷新</button>
            </div>
            
            <!-- 文件网格 -->
            <div class="files-grid" id="filesGrid">
                <div class="loading">加载中...</div>
            </div>
        </main>
    </div>
    
    <!-- 上传模态框 -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>上传文件</h3>
                <span class="close" onclick="closeUploadModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">📁</div>
                    <p>点击选择文件或拖拽文件到此处</p>
                    <p class="upload-info">支持 JPG, PNG, GIF, WebP 格式，最大 5MB</p>
                    <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
                </div>
                <div class="upload-progress" id="uploadProgress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">0%</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let files = [];
        
        // 页面加载时获取文件列表
        document.addEventListener('DOMContentLoaded', function() {
            loadFiles();
            setupUploadArea();
        });
        
        // 加载文件列表
        async function loadFiles() {
            try {
                const response = await fetch('upload.php?action=list');
                files = await response.json();
                renderFiles(files);
                updateStats();
            } catch (error) {
                console.error('加载文件列表失败:', error);
            }
        }
        
        // 渲染文件列表
        function renderFiles(filesToRender) {
            const grid = document.getElementById('filesGrid');
            
            if (filesToRender.length === 0) {
                grid.innerHTML = '<div class="no-files">暂无文件</div>';
                return;
            }
            
            grid.innerHTML = filesToRender.map(file => `
                <div class="file-item" data-type="${getFileType(file.type)}">
                    <div class="file-preview">
                        ${file.type.startsWith('image/') 
                            ? `<img src="${file.url}" alt="${file.name}" loading="lazy">` 
                            : `<div class="file-icon">${getFileIcon(file.type)}</div>`
                        }
                    </div>
                    <div class="file-info">
                        <div class="file-name" title="${file.name}">${file.name}</div>
                        <div class="file-meta">
                            <span class="file-size">${formatFileSize(file.size)}</span>
                            <span class="file-date">${new Date(file.modified * 1000).toLocaleDateString()}</span>
                        </div>
                    </div>
                    <div class="file-actions">
                        <button onclick="copyFileUrl('${file.url}')" class="btn btn-sm btn-info" title="复制链接">🔗</button>
                        <button onclick="deleteFile('${file.path}')" class="btn btn-sm btn-danger" title="删除">🗑️</button>
                    </div>
                </div>
            `).join('');
        }
        
        // 更新统计信息
        function updateStats() {
            document.getElementById('totalFiles').textContent = files.length;
            
            const totalSize = files.reduce((sum, file) => sum + file.size, 0);
            document.getElementById('totalSize').textContent = formatFileSize(totalSize);
        }
        
        // 文件搜索和筛选
        document.getElementById('searchFiles').addEventListener('input', filterFiles);
        document.getElementById('typeFilter').addEventListener('change', filterFiles);
        
        function filterFiles() {
            const searchTerm = document.getElementById('searchFiles').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            
            const filteredFiles = files.filter(file => {
                const matchesSearch = file.name.toLowerCase().includes(searchTerm);
                const matchesType = !typeFilter || getFileType(file.type) === typeFilter;
                return matchesSearch && matchesType;
            });
            
            renderFiles(filteredFiles);
        }
        
        // 刷新文件列表
        function refreshFiles() {
            loadFiles();
        }
        
        // 设置上传区域
        function setupUploadArea() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            
            uploadArea.addEventListener('click', () => fileInput.click());
            
            // 拖拽上传
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('drag-over');
            });
            
            uploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('drag-over');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('drag-over');
                handleFiles(e.dataTransfer.files);
            });
            
            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });
        }
        
        // 处理文件上传
        async function handleFiles(fileList) {
            const files = Array.from(fileList);
            
            for (let i = 0; i < files.length; i++) {
                await uploadFile(files[i], i + 1, files.length);
            }
            
            // 上传完成后刷新列表
            setTimeout(() => {
                loadFiles();
                closeUploadModal();
            }, 500);
        }
        
        // 上传单个文件
        async function uploadFile(file, current, total) {
            const formData = new FormData();
            formData.append('upload', file);
            
            const progressElement = document.getElementById('uploadProgress');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            
            progressElement.style.display = 'block';
            
            try {
                const response = await fetch('upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.uploaded) {
                    progressFill.style.width = '100%';
                    progressText.textContent = `文件 ${current}/${total} 上传完成`;
                } else {
                    throw new Error(result.error || '上传失败');
                }
            } catch (error) {
                console.error('上传失败:', error);
                alert(`文件 ${file.name} 上传失败: ${error.message}`);
            }
        }
        
        // 复制文件URL
        function copyFileUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('链接已复制到剪贴板');
            }).catch(() => {
                // 降级方案
                const textarea = document.createElement('textarea');
                textarea.value = url;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('链接已复制到剪贴板');
            });
        }
        
        // 删除文件
        async function deleteFile(path) {
            if (!confirm('确定要删除这个文件吗？')) return;
            
            try {
                const response = await fetch('upload.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `file=${encodeURIComponent(path)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadFiles();
                    alert('文件删除成功');
                } else {
                    throw new Error(result.error || '删除失败');
                }
            } catch (error) {
                console.error('删除失败:', error);
                alert('删除失败: ' + error.message);
            }
        }
        
        // 模态框控制
        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
            document.getElementById('uploadProgress').style.display = 'none';
        }
        
        // 工具函数
        function getFileType(mimeType) {
            if (mimeType.startsWith('image/')) return 'image';
            if (mimeType.startsWith('video/')) return 'video';
            if (mimeType.startsWith('audio/')) return 'audio';
            return 'document';
        }
        
        function getFileIcon(mimeType) {
            const type = getFileType(mimeType);
            const icons = {
                image: '🖼️',
                video: '🎥',
                audio: '🎵',
                document: '📄'
            };
            return icons[type] || '📄';
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
    
    <style>
        .file-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .stat-item {
            color: #6c757d;
        }
        
        .stat-value {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .file-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
        }
        
        .file-filters input,
        .file-filters select {
            max-width: 200px;
        }
        
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .file-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .file-item:hover {
            transform: translateY(-2px);
        }
        
        .file-preview {
            height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-icon {
            font-size: 3rem;
        }
        
        .file-info {
            padding: 1rem;
        }
        
        .file-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .file-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .file-actions {
            display: flex;
            gap: 0.5rem;
            padding: 0 1rem 1rem;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover,
        .upload-area.drag-over {
            border-color: #3498db;
            background: #f8f9ff;
        }
        
        .upload-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .upload-info {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .upload-progress {
            margin-top: 1rem;
        }
        
        .progress-bar {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #3498db;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            text-align: center;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        
        .no-files,
        .loading {
            grid-column: 1 / -1;
            text-align: center;
            color: #6c757d;
            padding: 3rem;
        }
    </style>
</body>
</html>