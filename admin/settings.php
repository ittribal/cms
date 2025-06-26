<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录状态和权限
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!$auth->hasPermission('setting.view')) {
    die('您没有权限访问此页面');
}

$pageTitle = '系统设置';
$currentUser = $auth->getCurrentUser();
$message = '';
$error = '';

// 处理设置保存
if ($_POST && isset($_POST['action'])) {
    if (!$auth->hasPermission('setting.edit')) {
        $error = '没有编辑权限';
    } else {
        switch ($_POST['action']) {
            case 'save_general':
                $result = saveGeneralSettings($_POST);
                break;
            case 'save_email':
                $result = saveEmailSettings($_POST);
                break;
            case 'save_seo':
                $result = saveSEOSettings($_POST);
                break;
            case 'save_security':
                $result = saveSecuritySettings($_POST);
                break;
            case 'save_appearance':
                $result = saveAppearanceSettings($_POST);
                break;
            case 'clear_cache':
                $result = clearSystemCache();
                break;
            case 'test_email':
                $result = testEmailSettings($_POST);
                break;
        }
        
        if (isset($result)) {
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// 保存通用设置
function saveGeneralSettings($data) {
    global $db, $auth;
    
    try {
        $settings = [
            'site_title' => $data['site_title'] ?? '',
            'site_tagline' => $data['site_tagline'] ?? '',
            'site_description' => $data['site_description'] ?? '',
            'site_keywords' => $data['site_keywords'] ?? '',
            'site_url' => $data['site_url'] ?? '',
            'admin_email' => $data['admin_email'] ?? '',
            'timezone' => $data['timezone'] ?? 'Asia/Shanghai',
            'date_format' => $data['date_format'] ?? 'Y-m-d',
            'time_format' => $data['time_format'] ?? 'H:i:s',
            'posts_per_page' => $data['posts_per_page'] ?? 10,
            'comment_moderation' => isset($data['comment_moderation']) ? 1 : 0,
            'user_registration' => isset($data['user_registration']) ? 1 : 0
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO site_settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
            $db->execute($sql, [$key, $value, $value]);
        }
        
        $auth->logAction('更新通用设置', '更新网站基本配置');
        return ['success' => true, 'message' => '通用设置保存成功'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '保存失败：' . $e->getMessage()];
    }
}

// 保存邮件设置
function saveEmailSettings($data) {
    global $db, $auth;
    
    try {
        $settings = [
            'email_smtp_host' => $data['email_smtp_host'] ?? '',
            'email_smtp_port' => $data['email_smtp_port'] ?? 587,
            'email_smtp_username' => $data['email_smtp_username'] ?? '',
            'email_smtp_password' => $data['email_smtp_password'] ?? '',
            'email_smtp_secure' => $data['email_smtp_secure'] ?? 'tls',
            'email_from_name' => $data['email_from_name'] ?? '',
            'email_from_email' => $data['email_from_email'] ?? ''
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO site_settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
            $db->execute($sql, [$key, $value, $value]);
        }
        
        $auth->logAction('更新邮件设置', '更新SMTP配置');
        return ['success' => true, 'message' => '邮件设置保存成功'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '保存失败：' . $e->getMessage()];
    }
}

// 保存SEO设置
function saveSEOSettings($data) {
    global $db, $auth;
    
    try {
        $settings = [
            'seo_meta_title' => $data['seo_meta_title'] ?? '',
            'seo_meta_description' => $data['seo_meta_description'] ?? '',
            'seo_meta_keywords' => $data['seo_meta_keywords'] ?? '',
            'seo_google_analytics' => $data['seo_google_analytics'] ?? '',
            'seo_google_search_console' => $data['seo_google_search_console'] ?? '',
            'seo_robots_txt' => $data['seo_robots_txt'] ?? '',
            'seo_sitemap_enabled' => isset($data['seo_sitemap_enabled']) ? 1 : 0,
            'seo_social_image' => $data['seo_social_image'] ?? ''
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO site_settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
            $db->execute($sql, [$key, $value, $value]);
        }
        
        $auth->logAction('更新SEO设置', '更新搜索引擎优化配置');
        return ['success' => true, 'message' => 'SEO设置保存成功'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '保存失败：' . $e->getMessage()];
    }
}

// 保存安全设置
function saveSecuritySettings($data) {
    global $db, $auth;
    
    try {
        $settings = [
            'security_login_attempts' => $data['security_login_attempts'] ?? 5,
            'security_lockout_duration' => $data['security_lockout_duration'] ?? 15,
            'security_session_timeout' => $data['security_session_timeout'] ?? 1440,
            'security_force_ssl' => isset($data['security_force_ssl']) ? 1 : 0,
            'security_two_factor' => isset($data['security_two_factor']) ? 1 : 0,
            'security_ip_whitelist' => $data['security_ip_whitelist'] ?? '',
            'security_admin_path' => $data['security_admin_path'] ?? 'admin'
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO site_settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
            $db->execute($sql, [$key, $value, $value]);
        }
        
        $auth->logAction('更新安全设置', '更新系统安全配置');
        return ['success' => true, 'message' => '安全设置保存成功'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '保存失败：' . $e->getMessage()];
    }
}

// 清理系统缓存
function clearSystemCache() {
    global $auth;
    
    try {
        $cacheDir = '../cache/';
        $files = glob($cacheDir . '*');
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $deletedCount++;
            }
        }
        
        $auth->logAction('清理缓存', "删除 {$deletedCount} 个缓存文件");
        return ['success' => true, 'message' => "缓存清理成功，删除了 {$deletedCount} 个文件"];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '缓存清理失败：' . $e->getMessage()];
    }
}

// 测试邮件设置
function testEmailSettings($data) {
    try {
        $testEmail = $data['test_email'] ?? '';
        if (empty($testEmail)) {
            return ['success' => false, 'message' => '请输入测试邮箱地址'];
        }
        
        $subject = '邮件配置测试 - ' . date('Y-m-d H:i:s');
        $message = "这是一封测试邮件，用于验证邮件配置是否正确。\n\n发送时间：" . date('Y-m-d H:i:s');
        
        // 这里应该使用实际的邮件发送逻辑
        // 为了演示，我们假设发送成功
        if (mail($testEmail, $subject, $message)) {
            return ['success' => true, 'message' => '测试邮件发送成功'];
        } else {
            return ['success' => false, 'message' => '测试邮件发送失败'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '测试失败：' . $e->getMessage()];
    }
}

// 获取当前设置
function getSetting($key, $default = '') {
    global $db;
    
    static $settings = null;
    if ($settings === null) {
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings[$key] ?? $default;
}

include '../templates/admin_header.php';
?>

<link rel="stylesheet" href="css/settings.css">

<div class="settings-page">
    <main class="main-content">
        <!-- 页面头部 -->
        <div class="page-header">
            <div class="header-left">
                <h1><i class="fas fa-cog"></i> 系统设置</h1>
                <p>配置网站的基本信息、邮件、SEO、安全等设置</p>
            </div>
            <div class="header-actions">
                <?php if ($auth->hasPermission('setting.edit')): ?>
                    <button onclick="clearCache()" class="btn btn-warning">
                        <i class="fas fa-broom"></i> 清理缓存
                    </button>
                <?php endif; ?>
                <a href="../index.php" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt"></i> 查看网站
                </a>
            </div>
        </div>

        <!-- 设置导航 -->
        <div class="settings-nav">
            <button class="nav-tab active" data-tab="general">
                <i class="fas fa-globe"></i> 通用设置
            </button>
            <button class="nav-tab" data-tab="email">
                <i class="fas fa-envelope"></i> 邮件设置
            </button>
            <button class="nav-tab" data-tab="seo">
                <i class="fas fa-search"></i> SEO设置
            </button>
            <button class="nav-tab" data-tab="security">
                <i class="fas fa-shield-alt"></i> 安全设置
            </button>
            <button class="nav-tab" data-tab="appearance">
                <i class="fas fa-palette"></i> 外观设置
            </button>
            <button class="nav-tab" data-tab="backup">
                <i class="fas fa-database"></i> 备份还原
            </button>
        </div>

        <!-- 通用设置 -->
        <div class="tab-content active" id="general">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-globe"></i> 网站基本信息</h3>
                </div>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_general">
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="site_title">网站标题 <span class="required">*</span></label>
                            <input type="text" id="site_title" name="site_title" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('site_title')); ?>" required>
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="site_tagline">网站副标题</label>
                            <input type="text" id="site_tagline" name="site_tagline" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('site_tagline')); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">网站描述</label>
                        <textarea id="site_description" name="site_description" class="form-control" 
                                  rows="3"><?php echo htmlspecialchars(getSetting('site_description')); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_keywords">网站关键词</label>
                        <input type="text" id="site_keywords" name="site_keywords" class="form-control" 
                               value="<?php echo htmlspecialchars(getSetting('site_keywords')); ?>"
                               placeholder="用逗号分隔多个关键词">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="site_url">网站URL</label>
                            <input type="url" id="site_url" name="site_url" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('site_url', 'http://localhost')); ?>">
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="admin_email">管理员邮箱</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('admin_email')); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-4">
                            <label for="timezone">时区</label>
                            <select id="timezone" name="timezone" class="form-control">
                                <option value="Asia/Shanghai" <?php echo getSetting('timezone') === 'Asia/Shanghai' ? 'selected' : ''; ?>>中国标准时间</option>
                                <option value="UTC" <?php echo getSetting('timezone') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="America/New_York" <?php echo getSetting('timezone') === 'America/New_York' ? 'selected' : ''; ?>>美国东部时间</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-4">
                            <label for="date_format">日期格式</label>
                            <select id="date_format" name="date_format" class="form-control">
                                <option value="Y-m-d" <?php echo getSetting('date_format') === 'Y-m-d' ? 'selected' : ''; ?>>2024-01-01</option>
                                <option value="m/d/Y" <?php echo getSetting('date_format') === 'm/d/Y' ? 'selected' : ''; ?>>01/01/2024</option>
                                <option value="d/m/Y" <?php echo getSetting('date_format') === 'd/m/Y' ? 'selected' : ''; ?>>01/01/2024</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-4">
                            <label for="posts_per_page">每页文章数</label>
                            <input type="number" id="posts_per_page" name="posts_per_page" class="form-control" 
                                   value="<?php echo getSetting('posts_per_page', 10); ?>" min="1" max="100">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="comment_moderation" 
                                       <?php echo getSetting('comment_moderation') ? 'checked' : ''; ?>>
                                启用评论审核
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="user_registration" 
                                       <?php echo getSetting('user_registration') ? 'checked' : ''; ?>>
                                允许用户注册
                            </label>
                        </div>
                    </div>
                    
                    <?php if ($auth->hasPermission('setting.edit')): ?>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 保存设置
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- 邮件设置 -->
        <div class="tab-content" id="email">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-envelope"></i> SMTP邮件配置</h3>
                </div>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="save_email">
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="email_smtp_host">SMTP服务器</label>
                            <input type="text" id="email_smtp_host" name="email_smtp_host" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('email_smtp_host')); ?>"
                                   placeholder="smtp.gmail.com">
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="email_smtp_port">SMTP端口</label>
                            <input type="number" id="email_smtp_port" name="email_smtp_port" class="form-control" 
                                   value="<?php echo getSetting('email_smtp_port', 587); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="email_smtp_username">用户名</label>
                            <input type="text" id="email_smtp_username" name="email_smtp_username" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('email_smtp_username')); ?>">
                        </div>
                        
                        <div class="form-group col-6">
                            <label for="email_smtp_password">密码</label>
                            <input type="password" id="email_smtp_password" name="email_smtp_password" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('email_smtp_password')); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-4">
                            <label for="email_smtp_secure">加密方式</label>
                            <select id="email_smtp_secure" name="email_smtp_secure" class="form-control">
                                <option value="tls" <?php echo getSetting('email_smtp_secure') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo getSetting('email_smtp_secure') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="" <?php echo getSetting('email_smtp_secure') === '' ? 'selected' : ''; ?>>无</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-4">
                            <label for="email_from_name">发件人姓名</label>
                            <input type="text" id="email_from_name" name="email_from_name" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('email_from_name')); ?>">
                        </div>
                        
                        <div class="form-group col-4">
                            <label for="email_from_email">发件人邮箱</label>
                            <input type="email" id="email_from_email" name="email_from_email" class="form-control" 
                                   value="<?php echo htmlspecialchars(getSetting('email_from_email')); ?>">
                        </div>
                    </div>
                    
                    <!-- 邮件测试 -->
                    <div class="form-section">
                        <h4><i class="fas fa-paper-plane"></i> 邮件测试</h4>
                        <div class="form-row">
                            <div class="form-group col-8">
                                <label for="test_email">测试邮箱</label>
                                <input type="email" id="test_email" name="test_email" class="form-control" 
                                       placeholder="输入邮箱地址进行测试">
                            </div>
                            <div class="form-group col-4">
                                <label>&nbsp;</label>
                                <button type="button" onclick="testEmail()" class="btn btn-info w-100">
                                    <i class="fas fa-paper-plane"></i> 发送测试邮件
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($auth->hasPermission('setting.edit')): ?>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 保存设置
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- 其他标签页内容... -->
        <div class="tab-content" id="seo">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> SEO优化设置</h3>
                </div>
                <div class="settings-placeholder">
                    <i class="fas fa-search"></i>
                    <p>SEO设置功能开发中...</p>
                </div>
            </div>
        </div>

        <div class="tab-content" id="security">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-shield-alt"></i> 安全设置</h3>
                </div>
                <div class="settings-placeholder">
                    <i class="fas fa-shield-alt"></i>
                    <p>安全设置功能开发中...</p>
                </div>
            </div>
        </div>

        <div class="tab-content" id="appearance">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-palette"></i> 外观设置</h3>
                </div>
                <div class="settings-placeholder">
                    <i class="fas fa-palette"></i>
                    <p>外观设置功能开发中...</p>
                </div>
            </div>
        </div>

        <div class="tab-content" id="backup">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-database"></i> 备份还原</h3>
                </div>
                <div class="settings-placeholder">
                    <i class="fas fa-database"></i>
                    <p>备份还原功能开发中...</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="js/settings.js"></script>

<?php include '../templates/admin_footer.php'; ?>