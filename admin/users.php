<?php
// admin/users.php - 用户管理主页面
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$db = Database::getInstance();

// 检查登录状态
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 检查权限
if (!$auth->hasPermission('user.view')) {
    die('您没有权限访问此页面');
}

$pageTitle = '用户管理';
$currentUser = $auth->getCurrentUser();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// 处理操作
if ($_POST) {
    $postAction = $_POST['action'] ?? '';
    
    switch ($postAction) {
        case 'add':
        case 'edit':
            $result = handleUserForm($_POST, $postAction);
            if ($result['success']) {
                $message = $result['message'];
                if ($postAction === 'add') {
                    header('Location: users.php?message=' . urlencode($message));
                    exit;
                }
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'delete':
            $result = deleteUser($_POST['id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'batch_delete':
            $result = batchDeleteUsers($_POST['ids']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'toggle_status':
            $result = toggleUserStatus($_POST['id'], $_POST['status']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'reset_password':
            $result = resetUserPassword($_POST['id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// 处理用户表单
function handleUserForm($data, $action) {
    global $db, $auth;
    
    try {
        // 验证必填字段
        if (empty($data['username']) || empty($data['email'])) {
            return ['success' => false, 'message' => '用户名和邮箱不能为空'];
        }
        
        // 验证邮箱格式
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => '邮箱格式不正确'];
        }
        
        // 检查用户名和邮箱重复
        $existingSql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $existingId = $action === 'edit' ? $data['id'] : 0;
        $existing = $db->fetchOne($existingSql, [$data['username'], $data['email'], $existingId]);
        
        if ($existing) {
            return ['success' => false, 'message' => '用户名或邮箱已存在'];
        }
        
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'real_name' => $data['real_name'] ?? '',
            'role' => $data['role'] ?? 'subscriber',
            'status' => $data['status'] ?? 'active',
            'bio' => $data['bio'] ?? '',
            'phone' => $data['phone'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($action === 'add') {
            // 新增用户需要密码
            if (empty($data['password'])) {
                return ['success' => false, 'message' => '密码不能为空'];
            }
            
            if (strlen($data['password']) < 6) {
                return ['success' => false, 'message' => '密码至少需要6位'];
            }
            
            $userData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $userData['created_at'] = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO users (" . implode(', ', array_keys($userData)) . ") 
                    VALUES (" . str_repeat('?,', count($userData) - 1) . "?)";
            $result = $db->execute($sql, array_values($userData));
            
            if ($result) {
                $userId = $db->getLastInsertId();
                $auth->logAction('添加用户', '用户ID: ' . $userId);
                return ['success' => true, 'message' => '用户添加成功'];
            }
        } else {
            // 编辑用户
            $userId = $data['id'];
            
            // 如果提供了新密码，则更新密码
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) {
                    return ['success' => false, 'message' => '密码至少需要6位'];
                }
                $userData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $setClause = implode(' = ?, ', array_keys($userData)) . ' = ?';
            $sql = "UPDATE users SET {$setClause} WHERE id = ?";
            $params = array_merge(array_values($userData), [$userId]);
            
            $result = $db->execute($sql, $params);
            
            if ($result) {
                $auth->logAction('编辑用户', '用户ID: ' . $userId);
                return ['success' => true, 'message' => '用户更新成功'];
            }
        }
        
        return ['success' => false, 'message' => '操作失败'];
        
    } catch (Exception $e) {
        error_log("User form error: " . $e->getMessage());
        return ['success' => false, 'message' => '操作失败：' . $e->getMessage()];
    }
}

// 删除用户
function deleteUser($id) {
    global $db, $auth;
    
    try {
        // 检查权限
        if (!$auth->hasPermission('user.delete')) {
            return ['success' => false, 'message' => '没有删除权限'];
        }
        
        // 不能删除当前登录用户
        if ($id == $auth->getCurrentUser()['id']) {
            return ['success' => false, 'message' => '不能删除当前登录用户'];
        }
        
        // 检查用户是否有关联内容
        $articleCount = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE author_id = ?", [$id])['count'];
        
        if ($articleCount > 0) {
            return ['success' => false, 'message' => '该用户还有关联文章，无法删除'];
        }
        
        $result = $db->execute("DELETE FROM users WHERE id = ?", [$id]);
        
        if ($result) {
            $auth->logAction('删除用户', '用户ID: ' . $id);
            return ['success' => true, 'message' => '用户删除成功'];
        }
        
        return ['success' => false, 'message' => '删除失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '删除失败：' . $e->getMessage()];
    }
}

// 批量删除用户
function batchDeleteUsers($ids) {
    global $db, $auth;
    
    try {
        if (empty($ids) || !is_array($ids)) {
            return ['success' => false, 'message' => '请选择要删除的用户'];
        }
        
        // 移除当前登录用户
        $currentUserId = $auth->getCurrentUser()['id'];
        $ids = array_filter($ids, function($id) use ($currentUserId) {
            return $id != $currentUserId;
        });
        
        if (empty($ids)) {
            return ['success' => false, 'message' => '没有可删除的用户'];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "DELETE FROM users WHERE id IN ({$placeholders})";
        
        $result = $db->execute($sql, $ids);
        
        if ($result) {
            $auth->logAction('批量删除用户', '用户数量: ' . count($ids));
            return ['success' => true, 'message' => '批量删除成功，共删除 ' . count($ids) . ' 个用户'];
        }
        
        return ['success' => false, 'message' => '批量删除失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '批量删除失败：' . $e->getMessage()];
    }
}

// 切换用户状态
function toggleUserStatus($id, $status) {
    global $db, $auth;
    
    try {
        $validStatus = ['active', 'inactive'];
        if (!in_array($status, $validStatus)) {
            return ['success' => false, 'message' => '无效的状态'];
        }
        
        // 不能禁用当前登录用户
        if ($id == $auth->getCurrentUser()['id'] && $status === 'inactive') {
            return ['success' => false, 'message' => '不能禁用当前登录用户'];
        }
        
        $result = $db->execute("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?", 
                              [$status, $id]);
        
        if ($result) {
            $auth->logAction('修改用户状态', "用户ID: {$id}, 状态: {$status}");
            return ['success' => true, 'message' => '状态更新成功'];
        }
        
        return ['success' => false, 'message' => '状态更新失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '状态更新失败：' . $e->getMessage()];
    }
}

// 重置用户密码
function resetUserPassword($id) {
    global $db, $auth;
    
    try {
        if (!$auth->hasPermission('user.edit')) {
            return ['success' => false, 'message' => '没有编辑权限'];
        }
        
        // 生成随机密码
        $newPassword = generateRandomPassword();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $result = $db->execute("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", 
                              [$hashedPassword, $id]);
        
        if ($result) {
            $auth->logAction('重置用户密码', '用户ID: ' . $id);
            return ['success' => true, 'message' => '密码重置成功，新密码：' . $newPassword];
        }
        
        return ['success' => false, 'message' => '密码重置失败'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '密码重置失败：' . $e->getMessage()];
    }
}

// 生成随机密码
function generateRandomPassword($length = 8) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// 根据不同操作显示不同页面
switch ($action) {
    case 'add':
        include 'user_form.php';
        break;
    case 'edit':
        $userId = $_GET['id'] ?? 0;
        if (!$userId) {
            header('Location: users.php?error=' . urlencode('用户ID无效'));
            exit;
        }
        include 'user_form.php';
        break;
    case 'view':
        $userId = $_GET['id'] ?? 0;
        include 'user_view.php';
        break;
    default:
        include 'user_list.php';
        break;
}
?>