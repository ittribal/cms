<!-- 页面底部 -->
        <footer class="admin-footer">
            <div class="footer-content">
                <div class="footer-left">
                    <p>&copy; <?php echo date('Y'); ?> CMS内容管理系统. 版权所有.</p>
                </div>
                <div class="footer-right">
                    <span>当前时间: <?php echo date('Y-m-d H:i:s'); ?></span>
                    <span class="separator">|</span>
                    <span>版本: v1.0.0</span>
                </div>
            </div>
        </footer>
    </div> <!-- .main-wrapper -->
</div> <!-- .admin-container -->

<!-- 通用模态框 -->
<div id="confirmModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="confirmTitle">确认操作</h3>
            <button type="button" class="close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">确定要执行此操作吗？</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">取消</button>
            <button type="button" class="btn btn-danger" id="confirmButton" onclick="executeConfirmAction()">确定</button>
        </div>
    </div>
</div>

<!-- Toast 提示框 -->
<div id="toastContainer" class="toast-container"></div>

<style>
.admin-footer {
    background: white;
    border-top: 1px solid #eee;
    padding: 1rem 1.5rem;
    margin-top: auto;
    flex-shrink: 0;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #6c757d;
    font-size: 0.85rem;
}

.footer-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.separator {
    color: #dee2e6;
}

/* 模态框样式 */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 10px 10px 0 0;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
    background: none;
    border: none;
    padding: 0;
    line-height: 1;
    transition: color 0.2s ease;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 0 0 10px 10px;
}

/* Toast 样式 */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1100;
    max-width: 350px;
}

.toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    margin-bottom: 10px;
    padding: 1rem;
    border-left: 4px solid;
    animation: slideInRight 0.3s ease;
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.toast-success {
    border-left-color: #27ae60;
    background: #d4edda;
    color: #155724;
}

.toast-error {
    border-left-color: #e74c3c;
    background: #f8d7da;
    color: #721c24;
}

.toast-warning {
    border-left-color: #f39c12;
    background: #fff3cd;
    color: #856404;
}

.toast-info {
    border-left-color: #17a2b8;
    background: #d1ecf1;
    color: #0c5460;
}

.toast-close {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    line-height: 1;
}

.toast-close:hover {
    opacity: 1;
}

/* 动画 */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0;
        transform: translateY(-30px) scale(0.9);
    }
    to { 
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOutRight {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}

/* 响应式 */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .footer-right {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .modal-content {
        margin: 5% auto;
        width: 95%;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .toast-container {
        top: 10px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
</style>

<script>
// 全局JavaScript函数

// 确认操作相关
let confirmCallback = null;

function confirmAction(message, callback, title = '确认操作') {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    confirmCallback = callback;
    document.getElementById('confirmModal').style.display = 'block';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    confirmCallback = null;
}

function executeConfirmAction() {
    if (confirmCallback && typeof confirmCallback === 'function') {
        confirmCallback();
    }
    closeConfirmModal();
}

// Toast 提示相关
function showToast(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    
    toast.className = `toast toast-${type}`;
    
    const icon = getToastIcon(type);
    
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
        <button class="toast-close" onclick="closeToast(this)">&times;</button>
    `;
    
    container.appendChild(toast);
    
    // 自动关闭
    setTimeout(() => {
        closeToast(toast.querySelector('.toast-close'));
    }, duration);
}

function getToastIcon(type) {
    const icons = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };
    return icons[type] || icons['info'];
}

function closeToast(button) {
    const toast = button.closest('.toast');
    toast.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 300);
}

// 表单验证相关
function validateForm(formElement) {
    const requiredFields = formElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// 数据表格相关
function sortTable(table, columnIndex, direction = 'asc') {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        if (direction === 'asc') {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// 搜索相关
function filterTable(table, searchTerm) {
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// 复选框全选相关
function toggleAllCheckboxes(masterCheckbox, checkboxClass) {
    const checkboxes = document.querySelectorAll(`.${checkboxClass}`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = masterCheckbox.checked;
    });
    updateBatchActions();
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 初始化所有提示工具
    initTooltips();
    
    // 初始化表单验证
    initFormValidation();
    
    // 初始化数据表格功能
    initDataTables();
    
    // 点击外部关闭模态框
    window.onclick = function(event) {
        const modal = document.getElementById('confirmModal');
        if (event.target === modal) {
            closeConfirmModal();
        }
    };
    
    // ESC键关闭模态框
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeConfirmModal();
        }
    });
});

function initTooltips() {
    // 初始化工具提示
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function initFormValidation() {
    // 初始化表单验证
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showToast('请填写所有必填字段', 'error');
            }
        });
    });
}

function initDataTables() {
    // 初始化数据表格功能
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        // 添加排序功能
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const direction = header.dataset.sortDirection === 'desc' ? 'asc' : 'desc';
                sortTable(table, index, direction);
                header.dataset.sortDirection = direction;
            });
        });
    });
}

function showTooltip(e) {
    // 显示工具提示的实现
}

function hideTooltip(e) {
    // 隐藏工具提示的实现
}

// 页面性能监控
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(() => {
            const perfData = performance.timing;
            const loadTime = perfData.loadEventEnd - perfData.navigationStart;
            
            if (loadTime > 3000) {
                console.warn('页面加载时间较长:', loadTime + 'ms');
            }
        }, 0);
    });
}

// 离线检测
window.addEventListener('online', function() {
    showToast('网络连接已恢复', 'success');
});

window.addEventListener('offline', function() {
    showToast('网络连接已断开', 'warning');
});
</script>

</body>
</html>