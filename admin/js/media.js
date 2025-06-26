// 媒体库管理 JavaScript

let currentView = 'grid';
let selectedFiles = new Set();
let allFiles = [];
let filteredFiles = [];
let currentPage = 1;
const itemsPerPage = 20;

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeMediaPage();
});

function initializeMediaPage() {
    setupEventListeners();
    loadMediaFiles();
    setupUploadArea();
}

function setupEventListeners() {
    // 视图切换
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchView(this.dataset.view);
        });
    });
    
    // 筛选控件
    document.getElementById('typeFilter').addEventListener('change', filterFiles);
    document.getElementById('searchBox').addEventListener('input', debounce(filterFiles, 300));
    
    // 上传表单
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', handleUpload);
    }
}

function setupUploadArea() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    
    if (!uploadArea || !fileInput) return;
    
    // 点击上传区域选择文件
    uploadArea.addEventListener('click', () => fileInput.click());
    
    // 文件选择
    fileInput.addEventListener('change', handleFileSelect);
    
    // 拖拽上传
    uploadArea.addEventListener('dragover', handleDragOver);
    uploadArea.addEventListener('dragleave', handleDragLeave);
    uploadArea.addEventListener('drop', handleDrop);
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('dragover');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('fileInput').files = files;
        previewFiles(files);
    }
}

function handleFileSelect(e) {
    const files = e.target.files;
    if (files.length > 0) {
        previewFiles(files);
    }
}

function previewFiles(files) {
    const preview = document.getElementById('filePreview');
    preview.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item';
        
        let previewContent = '';
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewItem.querySelector('.preview-content').innerHTML = 
                    `<img src="${e.target.result}" class="preview-image" alt="${file.name}">`;
            };
            reader.readAsDataURL(file);
            previewContent = '<div class="preview-content"><div class="loading">...</div></div>';
        } else {
            previewContent = `<div class="preview-content"><i class="fas fa-file file-icon"></i></div>`;
        }
        
        previewItem.innerHTML = `
            ${previewContent}
            <div class="preview-name">${file.name}</div>
            <button type="button" class="preview-remove" onclick="removePreviewFile(${index})">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        preview.appendChild(previewItem);
    });
}

function removePreviewFile(index) {
    const fileInput = document.getElementById('fileInput');
    const dt = new DataTransfer();
    const files = Array.from(fileInput.files);
    
    files.forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });
    
    fileInput.files = dt.files;
    previewFiles(fileInput.files);
}

function handleUpload(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const files = document.getElementById('fileInput').files;
    
    if (files.length === 0) {
        showToast('请选择要上传的文件', 'error');
        return;
    }
    
    // 显示进度条
    const progressContainer = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const uploadBtn = document.getElementById('uploadBtn');
    
    progressContainer.style.display = 'block';
    uploadBtn.disabled = true;
    
    // 模拟上传进度
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 90) progress = 90;
        
        progressFill.style.width = progress + '%';
        progressFill.textContent = Math.round(progress) + '%';
    }, 200);
    
    fetch('media.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        clearInterval(progressInterval);
        progressFill.style.width = '100%';
        progressFill.textContent = '100%';
        
        setTimeout(() => {
            if (data.includes('成功')) {
                showToast('文件上传成功', 'success');
                closeModal('uploadModal');
                loadMediaFiles();
            } else {
                showToast('上传失败，请重试', 'error');
            }
            
            progressContainer.style.display = 'none';
            uploadBtn.disabled = false;
            progressFill.style.width = '0%';
            document.getElementById('filePreview').innerHTML = '';
            document.getElementById('fileInput').value = '';
        }, 1000);
    })
    .catch(error => {
        clearInterval(progressInterval);
        showToast('上传失败：' + error.message, 'error');
        progressContainer.style.display = 'none';
        uploadBtn.disabled = false;
    });
}

function loadMediaFiles() {
    const container = document.getElementById('mediaContainer');
    const spinner = document.getElementById('loadingSpinner');
    
    spinner.style.display = 'flex';
    
    fetch('get_media_files.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allFiles = data.files;
                filteredFiles = [...allFiles];
                renderMediaFiles();
                updateFileCount();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('加载文件失败：' + error.message, 'error');
        })
        .finally(() => {
            spinner.style.display = 'none';
        });
}

function renderMediaFiles() {
    const container = document.getElementById('mediaContainer');
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageFiles = filteredFiles.slice(startIndex, endIndex);
    
    container.innerHTML = '';
    
    if (pageFiles.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <p>暂无媒体文件</p>
                <button onclick="showUploadModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 上传第一个文件
                </button>
            </div>
        `;
        return;
    }
    
    const mediaGrid = document.createElement('div');
    mediaGrid.className = currentView === 'grid' ? 'media-grid' : 'media-list';
    
    pageFiles.forEach(file => {
        const mediaItem = createMediaItem(file);
        mediaGrid.appendChild(mediaItem);
    });
    
    container.appendChild(mediaGrid);
    setupPagination();
}

function createMediaItem(file) {
    const item = document.createElement('div');
    item.className = 'media-item';
    item.dataset.id = file.id;
    
    const isImage = file.mime_type.startsWith('image/');
    const previewContent = isImage 
        ? `<img src="../${file.file_path}" alt="${file.original_name}">`
        : `<i class="fas ${getFileIcon(file.mime_type)} file-icon"></i>`;
    
    item.innerHTML = `
        <div class="media-checkbox">
            <input type="checkbox" onchange="toggleSelection(${file.id}, this.checked)">
        </div>
        <div class="media-preview">
            ${previewContent}
        </div>
        <div class="media-info">
            <div class="media-details">
                <div class="media-name" title="${file.original_name}">${file.original_name}</div>
                <div class="media-meta">
                    <span class="media-size">${formatFileSize(file.file_size)}</span>
                    <span class="media-date">${formatDate(file.created_at)}</span>
                </div>
            </div>
        </div>
        <div class="media-actions">
            <button class="action-btn" onclick="viewFileInfo(${file.id})" title="查看信息">
                <i class="fas fa-info"></i>
            </button>
            <button class="action-btn" onclick="copyFileUrl('${file.file_path}')" title="复制链接">
                <i class="fas fa-link"></i>
            </button>
            <button class="action-btn btn-danger" onclick="deleteFile(${file.id})" title="删除">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    // 双击查看详情
    item.addEventListener('dblclick', () => viewFileInfo(file.id));
    
    return item;
}

function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return 'fa-file-pdf';
    if (mimeType.includes('word')) return 'fa-file-word';
    if (mimeType.includes('excel')) return 'fa-file-excel';
    if (mimeType.includes('powerpoint')) return 'fa-file-powerpoint';
    if (mimeType.includes('text')) return 'fa-file-alt';
    if (mimeType.includes('zip') || mimeType.includes('rar')) return 'fa-file-archive';
    return 'fa-file';
}

function formatFileSize(bytes) {
    if (bytes >= 1073741824) {
        return (bytes / 1073741824).toFixed(2) + ' GB';
    } else if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    } else {
        return bytes + ' B';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('zh-CN');
}

function switchView(view) {
    currentView = view;
    
    // 更新按钮状态
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === view);
    });
    
    // 重新渲染
    renderMediaFiles();
}

function filterFiles() {
    const typeFilter = document.getElementById('typeFilter').value;
    const searchTerm = document.getElementById('searchBox').value.toLowerCase();
    
    filteredFiles = allFiles.filter(file => {
        let matchesType = true;
        let matchesSearch = true;
        
        if (typeFilter === 'image' && !file.mime_type.startsWith('image/')) {
            matchesType = false;
        } else if (typeFilter === 'document' && file.mime_type.startsWith('image/')) {
            matchesType = false;
        }
        
        if (searchTerm && !file.original_name.toLowerCase().includes(searchTerm)) {
            matchesSearch = false;
        }
        
        return matchesType && matchesSearch;
    });
    
    currentPage = 1;
    renderMediaFiles();
    updateFileCount();
}

function updateFileCount() {
    document.getElementById('fileCount').textContent = filteredFiles.length;
}

function setupPagination() {
    const totalPages = Math.ceil(filteredFiles.length / itemsPerPage);
    const paginationWrapper = document.getElementById('paginationWrapper');
    const pagination = document.getElementById('pagination');
    
    if (totalPages <= 1) {
        paginationWrapper.style.display = 'none';
        return;
    }
    
    paginationWrapper.style.display = 'block';
    pagination.innerHTML = '';
    
    // 上一页
    if (currentPage > 1) {
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.textContent = '‹';
        prevBtn.onclick = () => goToPage(currentPage - 1);
        pagination.appendChild(prevBtn);
    }
    
    // 页码
    for (let i = 1; i <= totalPages; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'page-btn';
        pageBtn.textContent = i;
        pageBtn.classList.toggle('active', i === currentPage);
        pageBtn.onclick = () => goToPage(i);
        pagination.appendChild(pageBtn);
    }
    
    // 下一页
    if (currentPage < totalPages) {
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.textContent = '›';
        nextBtn.onclick = () => goToPage(currentPage + 1);
        pagination.appendChild(nextBtn);
    }
}

function goToPage(page) {
    currentPage = page;
    renderMediaFiles();
}

function toggleSelection(fileId, selected) {
    if (selected) {
        selectedFiles.add(fileId);
    } else {
        selectedFiles.delete(fileId);
    }
    
    updateBatchActions();
    
    // 更新item样式
    const item = document.querySelector(`[data-id="${fileId}"]`);
    if (item) {
        item.classList.toggle('selected', selected);
    }
}

function updateBatchActions() {
    const batchActions = document.querySelector('.batch-actions');
    const selectedCount = document.querySelector('.selected-count');
    
    if (selectedFiles.size > 0) {
        batchActions.style.display = 'block';
        selectedCount.textContent = selectedFiles.size;
    } else {
        batchActions.style.display = 'none';
    }
}

function clearSelection() {
    selectedFiles.clear();
    document.querySelectorAll('.media-item').forEach(item => {
        item.classList.remove('selected');
        item.querySelector('input[type="checkbox"]').checked = false;
    });
    updateBatchActions();
}

function viewFileInfo(fileId) {
    const file = allFiles.find(f => f.id == fileId);
    if (!file) return;
    
    const isImage = file.mime_type.startsWith('image/');
    const previewContent = isImage 
        ? `<img src="../${file.file_path}" alt="${file.original_name}">`
        : `<i class="fas ${getFileIcon(file.mime_type)} file-icon"></i>`;
    
    const content = `
        <div class="file-info-grid">
            <div class="file-preview-large">
                ${previewContent}
            </div>
            <div class="file-details">
                <div class="detail-item">
                    <span class="detail-label">文件名</span>
                    <span class="detail-value">${file.original_name}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">文件大小</span>
                    <span class="detail-value">${formatFileSize(file.file_size)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">文件类型</span>
                    <span class="detail-value">${file.mime_type}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">上传时间</span>
                    <span class="detail-value">${formatDate(file.created_at)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">文件路径</span>
                    <span class="detail-value">${file.file_path}</span>
                </div>
            </div>
        </div>
        
        <form class="file-edit-form" onsubmit="updateFileInfo(event, ${file.id})">
            <input type="hidden" name="action" value="update_info">
            <input type="hidden" name="id" value="${file.id}">
            
            <div class="form-group">
                <label>替代文本 (Alt Text)</label>
                <input type="text" name="alt_text" class="form-control" 
                       value="${file.alt_text || ''}" placeholder="描述图片内容">
            </div>
            
            <div class="form-group">
                <label>说明文字</label>
                <textarea name="caption" class="form-control" rows="3" 
                          placeholder="文件说明">${file.caption || ''}</textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 保存
                </button>
                <button type="button" class="btn btn-info" onclick="copyFileUrl('${file.file_path}')">
                    <i class="fas fa-link"></i> 复制链接
                </button>
                <a href="../${file.file_path}" download class="btn btn-success">
                    <i class="fas fa-download"></i> 下载
                </a>
                <button type="button" class="btn btn-danger" onclick="deleteFile(${file.id})">
                    <i class="fas fa-trash"></i> 删除
                </button>
            </div>
        </form>
    `;
    
    document.getElementById('fileInfoContent').innerHTML = content;
    document.getElementById('fileInfoModal').style.display = 'block';
}

function updateFileInfo(event, fileId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('media.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes('成功')) {
            showToast('文件信息更新成功', 'success');
            loadMediaFiles();
        } else {
            showToast('更新失败', 'error');
        }
    })
    .catch(error => {
        showToast('更新失败：' + error.message, 'error');
    });
}

function copyFileUrl(filePath) {
    const url = window.location.origin + '/' + filePath;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('链接已复制到剪贴板', 'success');
        }).catch(() => {
            fallbackCopyTextToClipboard(url);
        });
    } else {
        fallbackCopyTextToClipboard(url);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('链接已复制到剪贴板', 'success');
    } catch (err) {
        showToast('复制失败，请手动复制', 'error');
    }
    
    document.body.removeChild(textArea);
}

function deleteFile(fileId) {
    confirmAction('确定要删除这个文件吗？删除后无法恢复。', () => {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', fileId);
        
        fetch('media.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('成功')) {
                showToast('文件删除成功', 'success');
                loadMediaFiles();
                closeModal('fileInfoModal');
            } else {
                showToast('删除失败', 'error');
            }
        })
        .catch(error => {
            showToast('删除失败：' + error.message, 'error');
        });
    });
}

function batchDelete() {
    if (selectedFiles.size === 0) {
        showToast('请选择要删除的文件', 'warning');
        return;
    }
    
    confirmAction(`确定要删除选中的 ${selectedFiles.size} 个文件吗？删除后无法恢复。`, () => {
        const formData = new FormData();
        formData.append('action', 'batch_delete');
        selectedFiles.forEach(id => formData.append('ids[]', id));
        
        fetch('media.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('成功')) {
                showToast('批量删除成功', 'success');
                clearSelection();
                loadMediaFiles();
            } else {
                showToast('批量删除失败', 'error');
            }
        })
        .catch(error => {
            showToast('批量删除失败：' + error.message, 'error');
        });
    });
}

function batchDownload() {
    if (selectedFiles.size === 0) {
        showToast('请选择要下载的文件', 'warning');
        return;
    }
    
    showToast('开始下载选中的文件...', 'info');
    
    selectedFiles.forEach(fileId => {
        const file = allFiles.find(f => f.id == fileId);
        if (file) {
            const link = document.createElement('a');
            link.href = '../' + file.file_path;
            link.download = file.original_name;
            link.click();
        }
    });
}

function showUploadModal() {
    document.getElementById('uploadModal').style.display = 'block';
}

function showCreateFolderModal() {
    // 创建文件夹功能（可以后续扩展）
    showToast('文件夹功能开发中...', 'info');
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    
    // 重置上传表单
    if (modalId === 'uploadModal') {
        document.getElementById('uploadForm').reset();
        document.getElementById('filePreview').innerHTML = '';
        document.getElementById('uploadProgress').style.display = 'none';
    }
}

function refreshMediaList() {
    loadMediaFiles();
    showToast('媒体库已刷新', 'success');
}

// 防抖函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 点击外部关闭模态框
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});