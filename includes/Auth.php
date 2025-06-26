<?php
// includes/Auth.php - 认证授权类 (操作统一的 users 表)

// 确保 Database 类已加载 (由 config.php 中的 spl_autoload_register 处理)
// Auth 类中会用到 functions.php 中定义的辅助函数，所以确保它也被引入
require_once ABSPATH . 'includes/functions.php'; 

class Auth {
    private $db;
    private $currentUser = null;
    private static $instance = null; // 用于存储 Auth 类的唯一实例

    // 权限定义 (注意：实际权限从数据库 roles 表的 permissions 字段动态读取)
    // 这里仅作为 Auth 类内部的默认或参考映射，实际验证依赖数据库
    // 权限结构示例：模块名.操作 (如 users.view, content.create)
    // * 表示所有权限
    private $permissionMap = [
        'super_admin' => ['*'], 
        'admin' => [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.assign_role',
            'content.view', 'content.create', 'content.edit', 'content.delete', 'content.publish',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'tags.view', 'tags.create', 'tags.edit', 'tags.delete',
            'media.view', 'media.upload', 'media.edit', 'media.delete',
            'comments.view', 'comments.moderate', 'comments.reply', 'comments.delete',
            'system.settings', 'system.logs', 'system.backup', 'system.cache',
            'emails.view', 'emails.send' // 邮件管理的权限
        ],
        'editor' => [
            'content.view', 'content.create', 'content.edit', 'content.publish',
            'categories.view', 'categories.edit',
            'tags.view', 'tags.create', 'tags.edit',
            'media.view', 'media.upload', 'media.edit',
            'comments.view', 'comments.moderate', 'comments.reply'
        ],
        'author' => [
            'content.view', 'content.create', 'content.edit_own', 'content.delete_own',
            'media.view', 'media.upload',
            'comments.view'
        ],
        'subscriber' => [
            'profile.view', 'profile.edit' // 可以查看和编辑自己的资料
        ]
    ];
    
    // 角色标签映射 (用于前端显示，方便统一管理)
    public static $roleLabels = [
        'super_admin' => '超级管理员',
        'admin' => '管理员',
        'editor' => '编辑',
        'author' => '作者',
        'subscriber' => '订阅者'
    ];

    // 私有构造函数，确保通过 getInstance() 获取实例
    private function __construct() {
        // 确保 session 已经启动 (config.php 中已处理)
        $this->db = Database::getInstance(); // 获取数据库单例
        $this->loadCurrentUser(); // 尝试从 Session 加载当前用户信息
        $this->checkRememberToken(); // 检查记住登录的 Cookie
    }

    // 获取 Auth 类的唯一实例（单例模式的入口）
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // 检查用户是否已登录
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $this->currentUser !== null;
    }
    
    // 获取当前用户信息
    public function getCurrentUser() {
        // 如果用户已登录但 currentUser 尚未完整加载（例如刚通过记住我登录），则强制重新加载
        if ($this->isLoggedIn() && $this->currentUser === null) {
            $this->loadCurrentUser(true); 
        }
        return $this->currentUser;
    }
    
    // 加载当前用户信息 (从数据库)
    private function loadCurrentUser($forceReload = false) {
        // 避免重复从数据库加载
        if (!$forceReload && $this->currentUser !== null && isset($_SESSION['user_id']) && $_SESSION['user_id'] === $this->currentUser['id']) {
            return; 
        }
        
        if (isset($_SESSION['user_id'])) {
            // 从统一的 users 表加载用户，并关联 roles 表获取角色名称和权限JSON
            $user = $this->db->fetchOne(
                "SELECT u.*, r.name as role_name, r.permissions as role_permissions 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ? AND u.status = 'active'",
                [$_SESSION['user_id']]
            );
            
            if ($user) {
                // 将角色权限从JSON字符串解码为PHP数组
                $user['role_permissions'] = json_decode($user['role_permissions'], true) ?: [];
                $this->currentUser = $user;
                $this->updateLastActivity(); // 更新用户最后活动时间
            } else {
                // 用户不存在或被禁用，清除会话，强制登出
                $this->logout(); 
            }
        }
    }
    
    // 用户登录逻辑
    public function login($username, $password, $rememberMe = false) {
        // 从统一的 users 表查询用户
        $user = $this->db->fetchOne(
            "SELECT u.*, r.name as role_name, r.permissions as role_permissions 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE (u.username = ? OR u.email = ?) AND u.status = 'active'",
            [$username, $username]
        );
        
        // 验证用户是否存在且密码正确
        if ($user && password_verify($password, $user['password'])) {
            // 登录成功：设置 Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id']; // 存储 role_id
            
            // 更新 currentUser 实例
            $user['role_permissions'] = json_decode($user['role_permissions'], true) ?: [];
            $this->currentUser = $user;
            
            $this->updateLoginInfo($user['id']); // 更新用户登录信息
            
            if ($rememberMe) {
                $this->setRememberToken($user['id']); // 设置记住登录 Cookie
            }
            
            $this->logAction($user['id'], '用户登录', '登录成功'); // 记录登录日志
            return true;
        } else {
            // 登录失败：记录失败尝试
            $this->logFailedLogin($username);
            return false;
        }
    }
    
    // 用户退出登录逻辑
    public function logout() {
        // 记录退出日志 (如果用户已登录)
        if ($this->isLoggedIn()) {
            $this->logAction($this->currentUser['id'], '用户退出', '主动退出');
        }
        
        // 清除所有 Session 数据
        session_unset();
        session_destroy();
        
        // 清除记住登录的 Cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            // 从数据库中删除对应的会话记录
            $this->db->delete('user_sessions', 'id = ?', [$_COOKIE['remember_token']]);
        }
        
        $this->currentUser = null; // 清除当前用户实例
    }
    
    // 检查用户是否有指定权限
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false; // 未登录用户没有任何权限
        }
    
        // 从当前用户实例中获取角色权限 (这些权限是从数据库 roles 表中动态加载的)
        $rolePermissions = $this->currentUser['role_permissions'] ?? [];
        
        // 超级管理员拥有所有权限
        if (in_array('*', $rolePermissions)) {
            return true;
        }

        // 检查通用模块权限 (如 'users.*', 'content.*')
        // 例如，如果 $permission 是 'users.view'，则检查 $rolePermissions 中是否有 'users.*'
        $parts = explode('.', $permission);
        if (count($parts) === 2 && in_array($parts[0] . '.*', $rolePermissions)) {
            return true;
        }

        // 检查具体权限
        return in_array($permission, $rolePermissions);
    }
    
    // 强制要求用户登录，如果未登录则重定向
    public function requireLogin($redirectUrl = '/admin/login.php') { 
        if (!$this->isLoggedIn()) {
            safe_redirect($redirectUrl); // 使用 functions.php 中的安全重定向函数
        }
    }
    
    // 强制要求用户拥有指定权限，如果没有则中止脚本执行
    public function requirePermission($permission, $errorMessage = '您没有权限执行此操作') {
        if (!$this->hasPermission($permission)) {
            http_response_code(403); // HTTP 403 Forbidden 状态码
            die($errorMessage); // 直接终止脚本并显示错误信息
        }
    }
    
    // 更新用户最后活动时间 (由 loadCurrentUser 调用)
    private function updateLastActivity() {
        if ($this->currentUser && $this->currentUser['id']) {
            $this->db->execute(
                "UPDATE users SET last_activity_at = NOW(), last_activity_ip = ? WHERE id = ?",
                [get_client_ip(), $this->currentUser['id']] // 使用 functions.php 中的 get_client_ip()
            );
        }
    }
    
    // 更新用户登录信息 (由 login 调用)
    private function updateLoginInfo($userId) {
        $this->db->execute(
            "UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?",
            [get_client_ip(), $userId] // 使用 functions.php 中的 get_client_ip()
        );
    }
    
    // 设置记住登录 Token (用于下次自动登录)
    private function setRememberToken($userId) {
        $token = bin2hex(random_bytes(32)); // 生成一个安全的随机 token
        $expires = time() + (30 * 24 * 60 * 60); // Token 有效期 30 天
        
        // 将 token 和相关信息保存到 user_sessions 表
        $this->db->execute(
            "INSERT INTO user_sessions (id, user_id, ip_address, user_agent, payload, last_activity) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $token,
                $userId,
                get_client_ip(), // 使用 functions.php 中的 get_client_ip()
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode(['remember' => true]), // payload 可以存储额外信息
                time()
            ]
        );
        
        // 设置记住登录的 Cookie (安全设置：httponly, secure, samesite)
        setcookie('remember_token', $token, [
            'expires' => $expires,
            'path' => '/',
            'domain' => '', 
            'secure' => isset($_SERVER['HTTPS']), // 仅在 HTTPS 下发送 Secure Cookie
            'httponly' => true,                   // 防止 JavaScript 访问 Cookie
            'samesite' => 'Lax'                   // 推荐的 SameSite 属性，防止 CSRF
        ]);
    }
    
    // 检查记住登录 Token (由构造函数调用，实现自动登录)
    public function checkRememberToken() {
        // 如果用户未登录但存在 remember_token Cookie
        if (!$this->isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            // 从 user_sessions 表中查询 token，并检查是否在有效期内
            $session = $this->db->fetchOne(
                "SELECT * FROM user_sessions WHERE id = ? AND last_activity > ?",
                [$token, time() - (30 * 24 * 60 * 60)] // Token 在过去 30 天内仍活跃
            );
            
            if ($session) {
                // 如果找到有效会话，尝试加载用户
                $user = $this->db->fetchOne(
                    "SELECT u.*, r.name as role_name, r.permissions as role_permissions
                     FROM users u 
                     JOIN roles r ON u.role_id = r.id 
                     WHERE u.id = ? AND u.status = 'active'",
                    [$session['user_id']]
                );
                
                if ($user) {
                    // 自动登录成功：设置 Session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role_id'] = $user['role_id']; 
                    
                    $user['role_permissions'] = json_decode($user['role_permissions'], true) ?: [];
                    $this->currentUser = $user;
                    $this->updateLastActivity(); // 更新最后活动时间
                    
                    // 刷新 token 的有效期 (在数据库中更新 last_activity)
                    $this->db->execute("UPDATE user_sessions SET last_activity = ? WHERE id = ?", [time(), $token]);
                    
                    return true;
                }
            }
            
            // 如果 token 无效或用户不存在/被禁用，则清除 Cookie
            setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            // 从数据库中删除无效会话记录
            $this->db->delete('user_sessions', 'id = ?', [$token]);
        }
        return false;
    }

    // 记录操作日志
    public function logAction($userId, $action, $tableName = null, $recordId = null, $details = null) {
        try {
            $this->db->execute(
                "INSERT INTO admin_logs (user_id, action, table_name, record_id, details, ip_address, user_agent, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userId,
                    $action,
                    $tableName,
                    $recordId,
                    json_encode($details, JSON_UNESCAPED_UNICODE), // 将详情数组转换为 JSON 字符串
                    get_client_ip(), // 使用 functions.php 中的 get_client_ip()
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]
            );
        } catch (Exception $e) {
            // 如果日志记录失败 (例如 admin_logs 表不存在或权限问题)，静默忽略错误，不影响主要业务逻辑
            error_log("日志记录失败: " . $e->getMessage());
        }
    }
    
    // 记录登录失败尝试 (可用于实现暴力破解防御)
    private function logFailedLogin($username) {
        $this->db->execute(
            "INSERT INTO login_attempts (username, ip_address, user_agent, success, attempted_at)
             VALUES (?, ?, ?, 0, NOW())",
            [$username, get_client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? ''] // 使用 functions.php 中的 get_client_ip()
        );
        // 这里可以根据实际需求添加IP锁定、验证码等逻辑
    }
}