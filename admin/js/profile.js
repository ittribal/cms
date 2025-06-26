// 个人设置页面 JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeProfilePage();
});

function initializeProfilePage() {
    setupTabSwitching();
    setupPasswordStrength();
    setupAvatarUpload();
    setupFormValidation();
    setupNotificationToggles();
}

// 设置选项卡切换
function setupTabSwitching() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // 移除所有活跃状态
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // 添加当前活跃状态
            this.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        });
    });
}

// 设置密码强度检测
function setupPasswordStrength() {
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthIndicator = document.getElementById('passwordStrength');
    
    if (!newPasswordInput || !strengthIndicator) return;
    
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        updatePasswordStrength(password, strengthIndicator);
        validatePasswordMatch();
    });
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    }
}

function updatePasswordStrength(password, indicator) {
    const strengthClasses = ['strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
    const strengthTexts = ['弱', '一般', '良好', '强'];
    
    // 移除所有强度类
    indicator.classList.remove(...strengthClasses);
    
    if (password.length === 0) {
        indicator.querySelector('.strength-text span').textContent = '请输入密码';
        return;
    }
    
    let score = 0;
    
    // 长度检查
    if (password.length >= 6) score++;
    if (password.length >= 10) score++;
    
    // 复杂度检查
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    
    // 限制最大分数
    score = Math.min(score, 4);
    const strengthIndex = Math.max(0, score - 1);
    
    if (score > 0) {
        indicator.classList.add(strengthClasses[strengthIndex]);
        indicator.querySelector('.strength-text span').textContent = strengthTexts[strengthIndex];
    }
}

function validatePasswordMatch() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (!newPassword || !confirmPassword) return;
    
    if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('密码不一致');
        confirmPassword.classList.add('is-invalid');
    } else {
        confirmPassword.setCustomValidity('');
        confirmPassword.classList.remove('is-invalid');
    }
}

// 设置头像上传
function setupAvatarUpload() {
    const avatarFile = document.getElementById('avatarFile');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (!avatarFile || !avatarPreview) return;
    
    avatarFile.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            // 验证文件类型
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showToast('只支持JPG、PNG、GIF格式的图片', 'error');
                this.value = '';
                return;
            }
            
            // 验证文件大小 (2MB)
            if (file.size > 2 * 1024 * 1024) {
                showToast('图片大小不能超过2MB', 'error');
                this.value = '';
                return;
            }
            
            // 预览图片
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
}

// 设置表单验证
function setupFormValidation() {
    const forms = document.querySelectorAll('.profile-form, .security-form, .preferences-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showToast('请检查表单输入', 'error');
            }
        });
    });
    
    // 实时验证邮箱
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.setCustomValidity('请输入有效的邮箱地址');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // 实时验证网站URL
    const websiteInput = document.getElementById('website');
    if (websiteInput) {
        websiteInput.addEventListener('blur', function() {
            if (this.value && !isValidURL(this.value)) {
                this.setCustomValidity('请输入有效的网站地址');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
}

// 设置通知切换
function setupNotificationToggles() {
    const toggles = document.querySelectorAll('.toggle-switch input');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            // 可以在这里添加实时保存逻辑
            console.log(`${this.name} 设置为: ${this.checked}`);
        });
    });
}

// 显示头像上传模态框
function showAvatarUpload() {
    document.getElementById('avatarUploadModal').style.display = 'block';
}

// 关闭模态框
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    
    // 重置头像上传表单
    if (modalId === 'avatarUploadModal') {
        const avatarForm = document.getElementById('avatarForm');
        if (avatarForm) {
            avatarForm.reset();
        }
    }
}

// 表单验证函数
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
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

// 邮箱验证
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// URL验证
function isValidURL(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

// 生成安全密码
function generateSecurePassword() {
    const length = 12;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    
    // 确保包含各种字符类型
    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';
    const symbols = '!@#$%^&*';
    
    password += lowercase[Math.floor(Math.random() * lowercase.length)];
    password += uppercase[Math.floor(Math.random() * uppercase.length)];
    password += numbers[Math.floor(Math.random() * numbers.length)];
    password += symbols[Math.floor(Math.random() * symbols.length)];
    
    // 填充剩余长度
    for (let i = 4; i < length; i++) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }
    
    // 打乱密码
    return password.split('').sort(() => Math.random() - 0.5).join('');
}

// 复制到剪贴板
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('已复制到剪贴板', 'success');
        }).catch(() => {
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
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
        showToast('已复制到剪贴板', 'success');
    } catch (err) {
        showToast('复制失败，请手动复制', 'error');
    }
    
    document.body.removeChild(textArea);
}

// 导出个人数据
function exportPersonalData() {
    confirmAction('确定要导出您的个人数据吗？', () => {
        showToast('正在准备数据导出...', 'info');
        
        // 这里应该调用后端API导出数据
        setTimeout(() => {
            showToast('数据导出完成，请检查下载文件', 'success');
        }, 2000);
    });
}

// 删除账户
function deleteAccount() {
    const confirmText = '删除账户';
    const userInput = prompt(`此操作不可逆！如果确定要删除账户，请输入"${confirmText}"确认：`);
    
    if (userInput === confirmText) {
        confirmAction('最后确认：您确定要永久删除账户吗？', () => {
            showToast('账户删除请求已提交，请联系管理员处理', 'warning');
        });
    } else if (userInput !== null) {
        showToast('确认文本不正确，操作取消', 'error');
    }
}

// 主题切换
function switchTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('preferred-theme', theme);
    showToast(`已切换到${theme === 'dark' ? '深色' : '浅色'}主题`, 'success');
}

// 语言切换
function switchLanguage(language) {
    // 这里应该实现语言切换逻辑
    showToast(`语言已切换为${language === 'zh-CN' ? '中文' : 'English'}`, 'success');
}

// 应用偏好设置
function applyPreferences() {
    const theme = document.getElementById('theme').value;
    const language = document.getElementById('language').value;
    
    if (theme !== 'auto') {
        switchTheme(theme);
    }
    
    // 其他偏好设置应用...
}

// 键盘快捷键
document.addEventListener('keydown', function(e) {
    // Ctrl+S 保存当前表单
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        const activeTab = document.querySelector('.tab-content.active');
        const form = activeTab ? activeTab.querySelector('form') : null;
        if (form) {
            form.requestSubmit();
            showToast('正在保存...', 'info');
        }
    }
    
    // ESC 关闭模态框
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
});

// 点击外部关闭模态框
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// 工具函数：显示提示消息
function showToast(message, type = 'info') {
    // 这里应该调用全局的showToast函数
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}

// 工具函数：确认操作
function confirmAction(message, callback) {
    // 这里应该调用全局的confirmAction函数
    if (typeof window.confirmAction === 'function') {
        window.confirmAction(message, callback);
    } else {
        if (confirm(message)) {
            callback();
        }
    }
}

// 页面卸载时的清理工作
window.addEventListener('beforeunload', function(e) {
    // 检查是否有未保存的更改
    const forms = document.querySelectorAll('form');
    let hasUnsavedChanges = false;
    
    forms.forEach(form => {
        const formData = new FormData(form);
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            if (input.dataset.originalValue !== undefined && 
                input.value !== input.dataset.originalValue) {
                hasUnsavedChanges = true;
            }
        });
    });
    
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '您有未保存的更改，确定要离开吗？';
        return e.returnValue;
    }
});

// 保存表单初始值用于检测更改
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.dataset.originalValue = input.value;
        
        input.addEventListener('input', function() {
            // 标记为已修改
            this.dataset.modified = 'true';
        });
    });
});