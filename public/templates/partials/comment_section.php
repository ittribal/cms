<?php
// public/templates/partials/comment_section.php - 评论区组件

// 确保 $article 变量已在父级作用域定义
if (!isset($article)) {
    return;
}

// 评论提交处理 (这里只做前端表单，实际后端API需要单独实现)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    // 实际的评论提交应该通过 AJAX 到 public/api/comments.php
    // 这里仅为演示
    $comment_author = sanitize_input($_POST['author_name'] ?? '');
    $comment_email = sanitize_input($_POST['author_email'] ?? '');
    $comment_content = sanitize_input($_POST['comment_content'] ?? '');
    $article_id_for_comment = intval($_POST['article_id'] ?? 0);

    if (!empty($comment_author) && !empty($comment_email) && !empty($comment_content) && $article_id_for_comment === $article['id']) {
        // 在这里调用后端 API (例如 public/api/comments.php)
        // 伪代码: apiFetch('/public/api/comments.php', { method: 'POST', body: new FormData(this) });
        // 然后显示成功或失败消息
        // 为了简单演示，这里不插入数据库，只显示消息
        // set_flash_message('评论已提交，等待审核。', 'success');
        // header("Location: article.php?slug=" . $article['slug'] . "#comments");
        // exit;
    } else {
        // set_flash_message('请填写所有必填项。', 'error');
        // header("Location: article.php?slug=" . $article['slug'] . "#comments");
        // exit;
    }
}
?>

<section class="comments-section" id="comments">
    <div class="container">
        <h2><i class="fas fa-comments"></i> 评论 (<?= count($comments) ?>)</h2>

        <div class="comment-list">
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-avatar">
                            <?= strtoupper(substr(esc_html($comment['author_name']), 0, 1)) ?>
                        </div>
                        <div class="comment-content-wrapper">
                            <div class="comment-author-name">
                                <strong><?= esc_html($comment['author_name']) ?></strong>
                                <span class="comment-date"><?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></span>
                            </div>
                            <p class="comment-text"><?= nl2br(esc_html($comment['content'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-comments">暂无评论，成为第一个评论者吧！</p>
            <?php endif; ?>
        </div>

        <div class="comment-form-section">
            <h3>发表评论</h3>
            <form id="commentForm" class="comment-form">
                <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                <input type="hidden" name="parent_id" value="0"> <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                <div class="form-group">
                    <label for="author_name">昵称 *</label>
                    <input type="text" id="author_name" name="author_name" required>
                </div>
                <div class="form-group">
                    <label for="author_email">邮箱 *</label>
                    <input type="email" id="author_email" name="author_email" required>
                </div>
                <div class="form-group">
                    <label for="comment_content">评论内容 *</label>
                    <textarea id="comment_content" name="comment_content" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">提交评论</button>
            </form>
        </div>
    </div>
</section>

<script>
    // comment_section.php 特有的 JS，这里只是演示，实际应放到 public/assets/js/article.js
    document.addEventListener('DOMContentLoaded', () => {
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(commentForm);
                // 假设后端 public/api/comments.php 处理评论提交
                try {
                    const response = await apiFetch('/public/api/comments.php', {
                        method: 'POST',
                        body: formData
                    });
                    if (response.success) {
                        alert('评论提交成功！等待审核。'); // 使用 alert 简化，实际应是更美观的 Toast
                        commentForm.reset();
                        // 刷新评论列表或动态添加新评论
                    } else {
                        alert('评论提交失败: ' + (response.message || '未知错误'));
                    }
                } catch (error) {
                    alert('评论提交时发生网络错误。');
                }
            });
        }
    });
</script>