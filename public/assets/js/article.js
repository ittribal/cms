// public/assets/js/article.js - 文章详情页特定 JavaScript

document.addEventListener('DOMContentLoaded', () => {
    initializeTableOfContents();
    initializeShareButtons();
    initializeCodeHighlighting(); // If you use a client-side highlighter
});

function initializeTableOfContents() {
    const articleContent = document.getElementById('articleContent');
    const tocList = document.getElementById('tocList');

    if (!articleContent || !tocList) return;

    const headings = articleContent.querySelectorAll('h1, h2, h3, h4, h5, h6');
    if (headings.length === 0) {
        tocList.innerHTML = '<li><span style="color: var(--text-color); opacity: 0.6; font-style: italic;">暂无目录</span></li>';
        return;
    }

    let tocHTML = '';
    headings.forEach((heading, index) => {
        const id = `heading-${index}`;
        heading.id = id; // Assign unique ID to each heading

        const level = parseInt(heading.tagName.substring(1)); // h1 -> 1, h2 -> 2 etc.
        // Adjust indentation based on heading level
        const paddingLeft = (level - 1) * 1.2; // Example: 1.2rem padding per level

        tocHTML += `
            <li style="padding-left: ${paddingLeft}rem;">
                <a href="#${id}">${heading.textContent}</a>
            </li>
        `;
    });
    tocList.innerHTML = tocHTML;

    // Smooth scroll for TOC links
    tocList.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function initializeShareButtons() {
    // Re-bind share buttons if they are dynamic or to ensure click handlers work
    window.shareTo = function(platform, url, title) {
        let shareUrl;
        switch (platform) {
            case 'weibo':
                shareUrl = `https://service.weibo.com/share/share.php?url=${encodeURIComponent(url)}&title=${encodeURIComponent(title)}`;
                break;
            case 'wechat':
                // For WeChat, typically show a QR code or just copy the URL
                navigator.clipboard.writeText(url)
                    .then(() => alert('链接已复制到剪贴板，请粘贴到微信分享'))
                    .catch(() => alert('复制失败，请手动复制链接: ' + url));
                return; // Prevent opening new window for WeChat
            case 'qq':
                shareUrl = `https://connect.qq.com/widget/shareqq/index.html?url=${encodeURIComponent(url)}&title=${encodeURIComponent(title)}`;
                break;
            default:
                return;
        }
        window.open(shareUrl, '_blank', 'width=600,height=400');
    };
}

function initializeCodeHighlighting() {
    // If you are using a library like highlight.js or Prism.js, initialize it here.
    // Example for highlight.js:
    // document.querySelectorAll('pre code').forEach((block) => {
    //     hljs.highlightElement(block);
    // });

    // For plain <pre><code> blocks, you might add a copy button
    document.querySelectorAll('pre code').forEach((block) => {
        const pre = block.parentElement;
        if (pre.tagName.toLowerCase() !== 'pre') return; // Ensure it's a code block inside pre

        const copyButton = document.createElement('button');
        copyButton.textContent = '复制代码';
        copyButton.className = 'copy-code-btn';
        pre.style.position = 'relative'; // Make sure pre is positioned for absolute button
        pre.appendChild(copyButton);

        copyButton.addEventListener('click', () => {
            navigator.clipboard.writeText(block.textContent)
                .then(() => {
                    copyButton.textContent = '已复制!';
                    setTimeout(() => { copyButton.textContent = '复制代码'; }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy text: ', err);
                    copyButton.textContent = '复制失败!';
                    setTimeout(() => { copyButton.textContent = '复制代码'; }, 2000);
                });
        });
    });

    // Add CSS for the copy button if not already in article.css
    const style = document.createElement('style');
    style.textContent = `
        .copy-code-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s ease, background-color 0.3s ease;
        }
        .copy-code-btn:hover {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.25);
        }
    `;
    document.head.appendChild(style);
}

// Comments form submission (AJAX)
document.addEventListener('DOMContentLoaded', () => {
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(commentForm);
            // Replace with your actual API endpoint for comments
            try {
                const response = await apiFetch('/public/api/comments.php', {
                    method: 'POST',
                    body: formData
                });
                if (response.success) {
                    alert('评论提交成功！等待审核。'); // Or use a nicer Toast
                    commentForm.reset();
                    // Optionally, you might want to dynamically add the comment to the list
                    // or reload comments if approved automatically
                } else {
                    alert('评论提交失败: ' + (response.message || '未知错误'));
                }
            } catch (error) {
                alert('评论提交时发生网络错误。');
            }
        });
    }
});