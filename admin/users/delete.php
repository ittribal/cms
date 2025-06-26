<?php
// ==================== admin/users/delete.php - 删除用户 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('users.delete');

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) {
    set_flash_message('参数错误', 'error');
    header('Location: index.php');
    exit;
}

// 不能删除自己
if ($user_id == $_SESSION['user_id']) {
    set_flash_message('不能删除自己的账户', 'error');
    header('Location: index.php');
    exit;
}

// 获取用户信息
$user = $db->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$user_id]);
if (!$user) {
    set_flash_message('用户不存在', 'error');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF验证失败', 'error');
    } else {
        try {
            // 检查是否有关联数据
            $article_count = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE author_id = ?", [$user_id])['count'];
            
            if ($article_count > 0) {
                // 将文章转移给当前用户
                $db->update('articles', ['author_id' => $_SESSION['user_id']], "author_id = $user_id");
            }
            
            // 删除用户
            $db->delete('admin_users', 'id = ?', [$user_id]);
            
            $auth->logAction($_SESSION['user_id'], 'user_delete', 'admin_users', $user_id, [
                'username' => $user['username'],
                'articles_transferred' => $article_count
            ]);
            
            set_flash_message('用户删除成功', 'success');
        } catch (Exception $e) {
            set_flash_message('删除失败: ' . $e->getMessage(), 'error');
        }
    }
    
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>删除用户 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">删除用户</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-outline">
                        <span class="icon">←</span> 返回列表
                    </a>
                </div>
            </div>
            
            <div class="content-card">
                <div class="delete-warning">
                    <div class="warning-icon">⚠️</div>
                    <h3>确认删除用户</h3>
                    <p>您即将删除用户 <strong><?= htmlspecialchars($user['username']) ?></strong>，此操作不可恢复。</p>
                    
                    <?php
                    $article_count = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE author_id = ?", [$user_id])['count'];
                    if ($article_count > 0):
                    ?>
                        <div class="data-transfer-info">
                            <h4>数据处理说明：</h4>
                            <ul>
                                <li>该用户创建的 <?= $article_count ?> 篇文章将转移给您</li>
                                <li>相关的操作日志将被保留</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger">
                                <span class="icon">🗑️</span> 确认删除
                            </button>
                            <a href="index.php" class="btn btn-outline">取消</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .delete-warning {
            text-align: center;
            padding: 2rem;
        }
        
        .warning-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .delete-warning h3 {
            color: #dc2626;
            margin-bottom: 1rem;
        }
        
        .delete-warning p {
            color: #374151;
            margin-bottom: 1.5rem;
        }
        
        .data-transfer-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: left;
        }
        
        .data-transfer-info h4 {
            color: #92400e;
            margin-bottom: 0.5rem;
        }
        
        .data-transfer-info ul {
            color: #92400e;
            margin-left: 1rem;
        }
        
        .form-actions {
            justify-content: center;
        }
    </style>
</body>
</html>