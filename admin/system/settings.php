
<?php
// ==================== admin/system/settings.php - 系统设置 ====================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$db = new Database();
$auth = new Auth($db);
$auth->requirePermission('system.settings');

// 处理设置保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF验证失败', 'error');
    } else {
        try {
            $db->beginTransaction();
            
            foreach ($_POST as $key => $value) {
                if ($key === 'csrf_token') continue;
                
                // 处理文件上传
                if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $upload_result = handle_file_upload($_FILES[$key], 'uploads/system/', $allowed_types);
                    $value = $upload_result['filepath'];
                }
                
                $existing = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
                
                if ($existing) {
                    $db->update('site_settings', ['setting_value' => $value], "setting_key = '{$key}'");
                } else {
                    $db->insert('site_settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'setting_type' => 'text'
                    ]);
                }
            }
            
            $db->commit();
            $auth->logAction($_SESSION['user_id'], 'settings_update', 'site_settings');
            set_flash_message('设置保存成功', 'success');
        } catch (Exception $e) {
            $db->rollback();
            set_flash_message('保存失败: ' . $e->getMessage(), 'error');
        }
    }
    
    header('Location: settings.php');
    exit;
}

// 获取当前设置
$settings_query = $db->fetchAll("SELECT * FROM site_settings ORDER BY setting_key");
$settings = [];
foreach ($settings_query as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - CMS系统</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-header">
                <h1 class="page-title">系统设置</h1>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div class="settings-layout">
                    <!-- 基本设置 -->
                    <div class="content-card">
                        <h3 class="card-title">基本设置</h3>
                        
                        <div class="form-group">
                            <label for="site_name">网站名称</label>
                            <input type="text" id="site_name" name="site_name" 
                                   value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="site_description">网站描述</label>
                            <textarea id="site_description" name="site_description" rows="3" 
                                      class="form-control"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">管理员邮箱</label>
                            <input type="email" id="admin_email" name="admin_email" 
                                   value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="site_logo">网站Logo</label>
                            <input type="file" id="site_logo" name="site_logo" accept="image/*" class="form-control">
                            <?php if (!empty($settings['site_logo'])): ?>
                                <div class="current-file">
                                    <img src="/<?= htmlspecialchars($settings['site_logo']) ?>" 
                                         alt="当前Logo" style="max-width: 200px; max-height: 100px; margin-top: 10px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 显示设置 -->
                    <div class="content-card">
                        <h3 class="card-title">显示设置</h3>
                        
                        <div class="form-group">
                            <label for="items_per_page">每页显示条目数</label>
                            <input type="number" id="items_per_page" name="items_per_page" 
                                   value="<?= htmlspecialchars($settings['items_per_page'] ?? '20') ?>" 
                                   min="1" max="100" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_format">日期格式</label>
                            <select id="date_format" name="date_format" class="form-control">
                                <option value="Y-m-d" <?= ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>2024-12-25</option>
                                <option value="d/m/Y" <?= ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>25/12/2024</option>
                                <option value="m/d/Y" <?= ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>12/25/2024</option>
                                <option value="Y年m月d日" <?= ($settings['date_format'] ?? '') === 'Y年m月d日' ? 'selected' : '' ?>>2024年12月25日</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">时区</label>
                            <select id="timezone" name="timezone" class="form-control">
                                <option value="Asia/Shanghai" <?= ($settings['timezone'] ?? '') === 'Asia/Shanghai' ? 'selected' : '' ?>>Asia/Shanghai (UTC+8)</option>
                                <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC (UTC+0)</option>
                                <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York (UTC-5)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- 安全设置 -->
                    <div class="content-card">
                        <h3 class="card-title">安全设置</h3>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_registration" value="1" 
                                       <?= ($settings['allow_registration'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                允许用户注册
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="require_email_verification" value="1" 
                                       <?= ($settings['require_email_verification'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                需要邮箱验证
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_login_attempts">最大登录尝试次数</label>
                            <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                   value="<?= htmlspecialchars($settings['max_login_attempts'] ?? '5') ?>" 
                                   min="1" max="20" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="session_timeout">会话超时时间(分钟)</label>
                            <input type="number" id="session_timeout" name="session_timeout" 
                                   value="<?= htmlspecialchars($settings['session_timeout'] ?? '60') ?>" 
                                   min="5" max="1440" class="form-control">
                        </div>
                    </div>
                    
                    <!-- 邮件设置 -->
                    <div class="content-card">
                        <h3 class="card-title">邮件设置</h3>
                        
                        <div class="form-group">
                            <label for="smtp_host">SMTP服务器</label>
                            <input type="text" id="smtp_host" name="smtp_host" 
                                   value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" 
                                   class="form-control" placeholder="smtp.example.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_port">SMTP端口</label>
                            <input type="number" id="smtp_port" name="smtp_port" 
                                   value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_username">SMTP用户名</label>
                            <input type="text" id="smtp_username" name="smtp_username" 
                                   value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_password">SMTP密码</label>
                            <input type="password" id="smtp_password" name="smtp_password" 
                                   value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="smtp_encryption" value="tls" 
                                       <?= ($settings['smtp_encryption'] ?? '') === 'tls' ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                启用TLS加密
                            </label>
                        </div>
                    </div>
                    
                    <!-- 维护模式 -->
                    <div class="content-card">
                        <h3 class="card-title">维护模式</h3>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="maintenance_mode" value="1" 
                                       <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                启用维护模式
                            </label>
                            <small class="form-help">启用后，普通用户将无法访问网站前台</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="maintenance_message">维护提示信息</label>
                            <textarea id="maintenance_message" name="maintenance_message" rows="3" 
                                      class="form-control"><?= htmlspecialchars($settings['maintenance_message'] ?? '网站正在维护中，请稍后访问。') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon">💾</span> 保存设置
                    </button>
                    <button type="button" class="btn btn-outline" onclick="location.reload()">
                        <span class="icon">🔄</span> 重置
                    </button>
                </div>
            </form>
        </main>
    </div>
    
    <style>
        .settings-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-bottom: 0;
        }
        
        .checkbox-label input[type="checkbox"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .current-file {
            margin-top: 10px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 6px;
        }
        
        @media (max-width: 768px) {
            .settings-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>