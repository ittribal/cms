<?php
// admin/system/emails.php - 邮件管理与日志

// 引入核心文件
require_once ABSPATH . 'includes/config.php';
require_once ABSPATH . 'includes/functions.php';
require_once ABSPATH . 'includes/Database.php';
require_once ABSPATH . 'includes/Auth.php';
require_once ABSPATH . 'includes/EmailService.php'; // 引入邮件服务类

$db = Database::getInstance();
$auth = Auth::getInstance();
$emailService = EmailService::getInstance(); // 获取 EmailService 实例

// 检查登录和权限
$auth->requirePermission('emails.view', '您没有权限访问邮件管理页面。'); // 假设有 emails.view 权限

$pageTitle = '邮件管理'; // 页面标题

// 处理 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('CSRF 验证失败，请刷新页面重试。', 'error');
        safe_redirect(SITE_URL . '/admin/system/emails.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_settings':
            $auth->requirePermission('system.settings', '您没有权限修改系统设置。'); // 假设修改邮件设置需要 system.settings 权限
            
            // 从表单获取邮件设置
            $emailSettings = [
                'email_smtp_host' => sanitize_input($_POST['smtp_host'] ?? ''),
                'email_smtp_port' => sanitize_input($_POST['smtp_port'] ?? ''),
                'email_smtp_username' => sanitize_input($_POST['smtp_username'] ?? ''),
                'email_smtp_password' => $_POST['smtp_password'] ?? '', // 密码不清理，直接传输哈希或存储
                'email_from_email' => sanitize_input($_POST['from_email'] ?? ''),
                'email_from_name' => sanitize_input($_POST['from_name'] ?? ''),
                'email_smtp_secure' => sanitize_input($_POST['smtp_secure'] ?? '') // tls, ssl, or empty
            ];
            
            try {
                $db->beginTransaction(); // 开始事务
                foreach ($emailSettings as $key => $value) {
                    // 使用 ON DUPLICATE KEY UPDATE 插入或更新设置项到 site_settings 表
                    $sql = "INSERT INTO site_settings (setting_key, setting_value, setting_type) 
                            VALUES (?, ?, 'text') 
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()";
                    $db->execute($sql, [$key, $value]);
                }
                $db->commit(); // 提交事务
                
                $auth->logAction($auth->getCurrentUser()['id'], '更新邮件设置', 'site_settings');
                set_flash_message('邮件设置保存成功！', 'success');
            } catch (Exception $e) {
                $db->rollback(); // 回滚事务
                set_flash_message('设置保存失败：' . $e->getMessage(), 'error');
            }
            safe_redirect(SITE_URL . '/admin/system/emails.php');
            break;

        case 'send_test':
            $auth->requirePermission('emails.send', '您没有权限发送测试邮件。'); // 假设有 emails.send 权限

            $testEmail = sanitize_input($_POST['test_email'] ?? '');
            if (!is_valid_email($testEmail)) {
                set_flash_message('请输入有效的测试邮箱地址。', 'error');
                safe_redirect(SITE_URL . '/admin/system/emails.php');
            }

            try {
                // 调用 EmailService 发送测试邮件
                $result = $emailService->sendMail(
                    $testEmail,
                    'CMS系统测试邮件 - ' . SITE_TITLE,
                    '<h2>恭喜！</h2><p>如果您收到这封邮件，说明您的 CMS 邮件配置正确。</p><p>这是来自您的 CMS 系统的一封测试邮件。</p><p>发送时间：' . date('Y-m-d H:i:s') . '</p>',
                    true // HTML 格式
                );
                
                if ($result) {
                    set_flash_message('测试邮件已成功发送到 ' . $testEmail . '。', 'success');
                } else {
                    set_flash_message('测试邮件发送失败。请检查 SMTP 配置和错误日志。', 'error');
                }
            } catch (Exception $e) {
                set_flash_message('发送测试邮件时发生异常：' . $e->getMessage(), 'error');
            }
            safe_redirect(SITE_URL . '/admin/system/emails.php');
            break;
            
        // 假设这里可以有清理邮件日志的操作
        // case 'clear_email_logs':
        //     $auth->requirePermission('system.logs', '您没有权限清理日志。');
        //     // ... 清理逻辑 ...
        //     break;
    }
}

// 获取当前邮件设置 (从 site_settings 表)
$emailSettings = [];
$settings_query = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'email_%'");
foreach ($settings_query as $setting) {
    $emailSettings[$setting['setting_key']] = $setting['setting_value'];
}

// 获取邮件统计 (近30天)
$emailStats = $emailService->getEmailStats(30);

// 获取邮件日志 (分页)
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ADMIN_ITEMS_PER_PAGE; // 使用后台通用每页条目数
$offset = ($page - 1) * $limit;

$emailLogs = $db->fetchAll("
    SELECT * FROM email_logs 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?", [$limit, $offset]);

$totalLogs = $db->fetchOne("SELECT COUNT(*) as count FROM email_logs")['count'];
$totalPages = ceil($totalLogs / $limit);

// 获取并显示闪存消息
$flash_message = get_flash_message();

// 引入后台头部模板
include ABSPATH . 'templates/admin_header.php'; 
?>

<main class="content">
    <div class="page-header">
        <h1 class="page-title">邮件管理</h1>
        <div class="page-actions">
            </div>
    </div>
    
    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_message['type'] ?>">
            <i class="fas fa-<?= $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash_message['message']) ?>
        </div>
    <?php endif; ?>
    
    <div class="content-card mb-4">
        <div class="card-header">
            <h3>邮件统计 (近30天)</h3>
        </div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($emailStats['total']) ?></div>
                    <div class="stat-label">总邮件数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-success"><?= number_format($emailStats['sent']) ?></div>
                    <div class="stat-label">发送成功</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-error"><?= number_format($emailStats['failed']) ?></div>
                    <div class="stat-label">发送失败</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number stat-pending"><?= number_format($emailStats['pending']) ?></div>
                    <div class="stat-label">待发送</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-card mb-4">
        <div class="card-header">
            <h3>SMTP 设置</h3>
        </div>
        <div class="card-body">
            <form method="POST" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="smtp_host">SMTP 服务器</label>
                        <input type="text" id="smtp_host" name="smtp_host" class="form-control" 
                               value="<?= esc_attr($emailSettings['email_smtp_host'] ?? '') ?>" 
                               placeholder="smtp.example.com">
                    </div>
                    <div class="form-group col-6">
                        <label for="smtp_port">端口</label>
                        <input type="number" id="smtp_port" name="smtp_port" class="form-control" 
                               value="<?= esc_attr($emailSettings['email_smtp_port'] ?? '587') ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="smtp_username">用户名</label>
                        <input type="text" id="smtp_username" name="smtp_username" class="form-control" 
                               value="<?= esc_attr($emailSettings['email_smtp_username'] ?? '') ?>">
                    </div>
                    <div class="form-group col-6">
                        <label for="smtp_password">密码</label>
                        <input type="password" id="smtp_password" name="smtp_password" class="form-control" 
                               value="<?= esc_attr($emailSettings['email_smtp_password'] ?? '') ?>">
                        <small class="form-text">出于安全考虑，密码在此处不会明文显示，输入新密码将覆盖旧密码。</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="smtp_secure">加密方式</label>
                        <select name="smtp_secure" id="smtp_secure" class="form-control">
                            <option value="tls" <?= ($emailSettings['email_smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= ($emailSettings['email_smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="" <?= empty($emailSettings['email_smtp_secure']) ? 'selected' : '' ?>>无</option>
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label for="from_email">发件人邮箱</label>
                        <input type="email" id="from_email" name="from_email" class="form-control" 
                               value="<?= esc_attr($emailSettings['email_from_email'] ?? '') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label for="from_name">发件人名称</label>
                        <input type="text" id="from_name" name="from_name" class="form-control" 
                               value="<?= esc_attr($emailSettings['email_from_name'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存设置
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="content-card mb-4">
        <div class="card-header">
            <h3>发送测试邮件</h3>
        </div>
        <div class="card-body">
            <form method="POST" class="test-form">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="send_test">
                <div class="form-group">
                    <label for="test_email">测试邮箱</label>
                    <div style="display: flex; gap: 1rem;">
                        <input type="email" id="test_email" name="test_email" class="form-control" 
                               placeholder="输入要测试的邮箱地址" required>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane"></i> 发送测试
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="content-card">
        <div class="card-header">
            <h3>邮件发送日志</h3>
        </div>
        <div class="card-body">
            <div class="logs-table table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>收件人</th>
                            <th>主题</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>发送时间</th>
                            <th>错误信息</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($emailLogs)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted p-3">暂无邮件发送日志。</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($emailLogs as $log): ?>
                            <tr>
                                <td><?= esc_html($log['to_email']) ?></td>
                                <td><?= esc_html($log['subject']) ?></td>
                                <td>
                                    <span class="badge status-<?= esc_attr($log['status']) ?>">
                                        <?= ucfirst(esc_html($log['status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                                <td><?= $log['sent_at'] ? date('Y-m-d H:i', strtotime($log['sent_at'])) : '—' ?></td>
                                <td title="<?= esc_attr($log['error_message'] ?? '') ?>">
                                    <?= esc_html(truncate_text($log['error_message'] ?? '—', 50)) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include ABSPATH . 'templates/admin_footer.php'; // 引入后台底部模板 ?>

<style>
/* 样式已从 admin/assets/css/settings.css 和 admin/assets/css/admin.css 加载 */
/* 这里仅为方便展示特定样式 */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.stat-number.stat-success { color: #27ae60; }
.stat-number.stat-error { color: #e74c3c; }
.stat-number.stat-pending { color: #f39c12; }

.test-form .form-group > div {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
}

.logs-table table {
    min-width: 700px; /* 确保表格在小屏幕下能滚动 */
}

/* 状态徽章颜色，与 comments.css 中状态徽章类似 */
.badge.status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.badge.status-sent { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.badge.status-failed { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

/* Responsive Adjustments */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    .test-form .form-group > div {
        flex-direction: column;
        align-items: stretch;
    }
    .test-form .btn {
        width: 100%;
    }
}
</style>