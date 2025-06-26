<?php
// includes/SimpleAuth.php - 简化的认证类

class SimpleAuth {
    private $db;
    private $currentUser = null;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
        $this->loadCurrentUser();
    }
    
    // 检查是否已登录
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $this->currentUser !== null;
    }
    
    // 获取当前用户信息
    public function getCurrentUser() {
        return $this->currentUser ?: [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'super_admin',
            'status' => 'active'
        ];
    }
    
    // 加载当前用户信息
    private function loadCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ? AND status = 'active'",
                [$_SESSION['user_id']]
            );
            
            if ($user) {
                $this->currentUser = $user;
            } else {
                // 如果用户表不存在或用户不存在，创建默认用户
                $this->currentUser = [
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@example.com',
                    'role' => 'super_admin',
                    'status' => 'active'
                ];
            }
        } else {
            // 自动登录默认管理员（仅用于演示）
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'super_admin';
            
            $this->currentUser = [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'role' => 'super_admin',
                'status' => 'active'
            ];
        }
    }
    
    // 记录用户操作日志
    public function logAction($action, $description = '') {
        try {
            // 检查日志表是否存在
            $tableExists = $this->db->fetchOne("SHOW TABLES LIKE 'admin_logs'");
            
            if ($tableExists && $this->currentUser) {
                $this->db->execute(
                    "INSERT INTO admin_logs (user_id, action, description, ip_address, user_agent, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())",
                    [
                        $this->currentUser['id'],
                        $action,
                        $description,
                        $this->getClientIP(),
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]
                );
            }
        } catch (Exception $e) {
            // 静默忽略日志错误，不影响主要功能
            error_log("Log action failed: " . $e->getMessage());
        }
    }
    
    // 检查用户权限
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $this->currentUser['role'];
        
        // 超级管理员拥有所有权限
        if ($role === 'super_admin') {
            return true;
        }
        
        // 管理员权限
        if ($role === 'admin') {
            return true;
        }
        
        // 其他角色的权限可以在这里定义
        return false;
    }
    
    // 获取客户端IP
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // 检查用户是否是管理员
    public function isAdmin() {
        return $this->hasPermission('admin');
    }
    
    // 强制要求登录
    public function requireLogin($redirectUrl = 'login.php') {
        if (!$this->isLoggedIn()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    // 用户退出
    public function logout() {
        session_unset();
        session_destroy();
        $this->currentUser = null;
    }
}
?>