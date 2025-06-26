// 前台主要JavaScript功能

// DOM加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
    initSearchFeatures();
    initLazyLoading();
    initBackToTop();
    initReadingProgress();
});

// 导航功能
function initNavigation() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuBtn && navMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
        });
        
        // 点击链接时关闭移动菜单
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                mobileMenuBtn.classList.remove('active');
            });
        });
    }
}

// 搜索功能
function initSearchFeatures() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        // 搜索建议
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (this.value.length > 2) {
                    showSearchSuggestions(this.value);
                } else {
                    hideSearchSuggestions();
                }
            }, 300);
        });
        
        // 点击外部隐藏建议
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-form')) {
                hideSearchSuggestions();
            }
        });
    }
}

// 搜索建议
async function showSearchSuggestions(query) {
    try {
        const response = await fetch(`search_suggestions.php?q=${encodeURIComponent(query)}`);
        const suggestions = await response.json();
        
        if (suggestions.length > 0) {
            renderSearchSuggestions(suggestions);
        }
    } catch (error) {
        console.error('获取搜索建议失败:', error);
    }
}

function renderSearchSuggestions(suggestions) {
    let suggestionsBox = document.querySelector('.search-suggestions');
    
    if (!suggestionsBox) {
        suggestionsBox = document.createElement('div');
        suggestionsBox.className = 'search-suggestions';
        document.querySelector('.search-form').appendChild(suggestionsBox);
    }
    
    suggestionsBox.innerHTML = suggestions.map(item => `
        <div class="suggestion-item" onclick="selectSuggestion('${item.title}', '${item.slug}')">
            <div class="suggestion-title">${item.title}</div>
            <div class="suggestion-excerpt">${item.excerpt}</div>
        </div>
    `).join('');
    
    suggestionsBox.style.display = 'block';
}

function hideSearchSuggestions() {
    const suggestionsBox = document.querySelector('.search-suggestions');
    if (suggestionsBox) {
        suggestionsBox.style.display = 'none';
    }
}

function selectSuggestion(title, slug) {
    window.location.href = `article.php?slug=${slug}`;
}

// 懒加载图片
function initLazyLoading() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
}

// 返回顶部按钮
function initBackToTop() {
    const backToTopBtn = createBackToTopButton();
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.style.display = 'flex';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });
    
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

function createBackToTopButton() {
    const btn = document.createElement('button');
    btn.className = 'back-to-top';
    btn.innerHTML = '↑';
    btn.title = '返回顶部';
    btn.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #3498db;
        color: white;
        border: none;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 1000;
        transition: all 0.3s ease;
    `;
    
    btn.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
    });
    
    btn.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
    
    document.body.appendChild(btn);
    return btn;
}

// 阅读进度条
function initReadingProgress() {
    if (document.querySelector('.article-content')) {
        const progressBar = createProgressBar();
        
        window.addEventListener('scroll', function() {
            const article = document.querySelector('.article-body');
            if (article) {
                const articleTop = article.offsetTop;
                const articleHeight = article.offsetHeight;
                const windowHeight = window.innerHeight;
                const scrollTop = window.pageYOffset;
                
                const progress = Math.max(0, Math.min(100, 
                    ((scrollTop - articleTop + windowHeight) / articleHeight) * 100
                ));
                
                progressBar.style.width = progress + '%';
            }
        });
    }
}

function createProgressBar() {
    const progressContainer = document.createElement('div');
    progressContainer.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: rgba(52, 152, 219, 0.2);
        z-index: 1000;
    `;
    
    const progressBar = document.createElement('div');
    progressBar.style.cssText = `
        height: 100%;
        background: #3498db;
        width: 0%;
        transition: width 0.3s ease;
    `;
    
    progressContainer.appendChild(progressBar);
    document.body.appendChild(progressContainer);
    
    return progressBar;
}

// 文章分享功能
function shareToWeChat() {
    // 微信分享通常需要通过微信JS-SDK
    alert('请复制链接在微信中分享');
    copyCurrentUrl();
}

function shareToWeibo() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    const weiboUrl = `https://service.weibo.com/share/share.php?url=${url}&title=${title}`;
    window.open(weiboUrl, '_blank', 'width=600,height=400');
}

function shareToQQ() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    const qqUrl = `https://connect.qq.com/widget/shareqq/index.html?url=${url}&title=${title}`;
    window.open(qqUrl, '_blank', 'width=600,height=400');
}

function copyLink() {
    copyCurrentUrl();
}

function copyCurrentUrl() {
    const url = window.location.href;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('链接已复制到剪贴板');
        }).catch(() => {
            fallbackCopy(url);
        });
    } else {
        fallbackCopy(url);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showToast('链接已复制到剪贴板');
    } catch (err) {
        showToast('复制失败，请手动复制');
    }
    
    document.body.removeChild(textarea);
}

// 提示消息
function showToast(message, duration = 3000) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: #2c3e50;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    // 显示动画
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // 隐藏动画
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, duration);
}

// 图片点击放大
document.addEventListener('click', function(e) {
    if (e.target.tagName === 'IMG' && e.target.closest('.article-body')) {
        showImageModal(e.target.src, e.target.alt);
    }
});

function showImageModal(src, alt) {
    const modal = document.createElement('div');
    modal.className = 'image-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        cursor: pointer;
    `;
    
    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    img.style.cssText = `
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        border-radius: 8px;
    `;
    
    modal.appendChild(img);
    document.body.appendChild(modal);
    
    modal.addEventListener('click', function() {
        document.body.removeChild(modal);
    });
    
    // ESC键关闭
    const handleEsc = function(e) {
        if (e.key === 'Escape') {
            document.body.removeChild(modal);
            document.removeEventListener('keydown', handleEsc);
        }
    };
    document.addEventListener('keydown', handleEsc);
}

// 搜索历史管理
function saveSearchHistory(keyword) {
    if (!keyword.trim()) return;
    
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    history = history.filter(item => item !== keyword);
    history.unshift(keyword);
    history = history.slice(0, 10); // 保留最近10个
    
    localStorage.setItem('searchHistory', JSON.stringify(history));
}

function getSearchHistory() {
    return JSON.parse(localStorage.getItem('searchHistory') || '[]');
}

// 暗色模式切换（可选功能）
function initDarkMode() {
    const darkModeToggle = document.createElement('button');
    darkModeToggle.innerHTML = '🌙';
    darkModeToggle.className = 'dark-mode-toggle';
    darkModeToggle.title = '切换暗色模式';
    darkModeToggle.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: #f8f9fa;
        cursor: pointer;
        font-size: 1.2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        z-index: 1000;
        transition: all 0.3s ease;
    `;
    
    // 检查本地存储的主题设置
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
        darkModeToggle.innerHTML = '☀️';
    }
    
    darkModeToggle.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        
        this.innerHTML = isDark ? '☀️' : '🌙';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
    
    // 不在移动设备上显示（避免与返回顶部按钮冲突）
    if (window.innerWidth > 768) {
        document.body.appendChild(darkModeToggle);
    }
}

// 性能优化：防抖函数
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

// 性能优化：节流函数
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// 页面可见性API - 暂停不必要的操作
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // 页面隐藏时暂停动画等
        console.log('页面已隐藏');
    } else {
        // 页面重新可见时恢复
        console.log('页面已显示');
    }
});
7. 搜索建议API (public/search_suggestions.php)
php
<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$db = Database::getInstance();

// 搜索文章标题和内容
$searchTerm = "%{$query}%";
$sql = "SELECT title, slug, 
               SUBSTRING(content, 1, 100) as excerpt
        FROM articles 
        WHERE status = 'published' 
        AND (title LIKE ? OR content LIKE ?) 
        ORDER BY 
            CASE WHEN title LIKE ? THEN 1 ELSE 2 END,
            views DESC
        LIMIT 5";

$suggestions = $db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);

// 清理excerpt中的HTML标签
foreach ($suggestions as &$suggestion) {
    $suggestion['excerpt'] = strip_tags($suggestion['excerpt']) . '...';
}

echo json_encode($suggestions);
?>