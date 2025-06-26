<?php // <-- 确保这是文件的第一行第一列，前面没有任何字符
// admin/system/dashboard_debug.php - 仪表盘调试文件 (所有CSS内联，仅用于诊断)

// 核心引导：确保 config.php 首先被引入
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php'; 

// 引入其他核心类和函数
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php';
require_once ABSPATH . 'includes/Auth.php';

$db = Database::getInstance();
$auth = Auth::getInstance();
$auth->requireLogin(); // 强制要求登录

// 获取统计数据
$stats = [
    'users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'], /* 统一用户表 */
    'articles' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status != 'archived'")['count'], // 不统计归档文章
    'categories' => $db->fetchOne("SELECT COUNT(*) as count FROM categories")['count'],
    'media' => $db->fetchOne("SELECT COUNT(*) as count FROM media_files")['count']
];

// 获取最新文章
$recent_articles = $db->fetchAll(
    "SELECT a.id, a.title, a.slug, a.status, a.created_at,
            u.username as author_name, c.name as category_name
     FROM articles a 
     JOIN users u ON a.author_id = u.id /* 统一用户表 */
     LEFT JOIN categories c ON a.category_id = c.id 
     WHERE a.status = 'published' /* 只显示已发布的文章 */
     ORDER BY a.created_at DESC 
     LIMIT 5"
);

// 获取最新日志
$recent_logs = $db->fetchAll(
    "SELECT l.action, l.created_at, u.username 
     FROM admin_logs l 
     LEFT JOIN users u ON l.user_id = u.id /* 统一用户表 */
     ORDER BY l.created_at DESC 
     LIMIT 10"
);

$currentUser = $auth->getCurrentUser();

// 设置页面标题
$pageTitle = '控制台';

// 获取并显示闪存消息
$flash_message = get_flash_message();

// 引入后台头部模板 (只包含 HTML body 开头，不引入外部CSS)
// 由于CSS内联在此文件，不包含 admin_header.php
// 但为了保持一致性，我们会把 admin_header.php 的 HTML 部分复制过来

// ======================================================================================================
// 注意：以下是 admin_header.php 的内容，以及 admin.css 的内容，全部嵌入到这个文件中
// ======================================================================================================
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($pageTitle) ?> | <?= esc_html(SITE_TITLE) ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/public/assets/favicon.ico">

    <style>
    /* =====================================================
       CMS Admin Base Styles - 管理员后台基础样式
       ===================================================== */

    /* 重置样式 */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* 基础样式 */
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: #f5f6fa;
        color: #2c3e50;
        line-height: 1.6;
    }

    /* 容器布局 */
    .admin-container {
        display: flex;
        min-height: 100vh;
    }

    /* 侧边栏 */
    .sidebar {
        width: 260px;
        background: linear-gradient(135deg, #2c3e50, #3498db);
        color: white;
        flex-shrink: 0;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-header h2 {
        font-size: 1.2rem;
        font-weight: 600;
    }

    .sidebar-nav {
        padding: 1rem 0;
    }

    .nav-item {
        margin-bottom: 0.25rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }

    .nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: white;
        border-left-color: #3498db;
    }

    .nav-link.active {
        background: rgba(255,255,255,0.15);
        color: white;
        border-left-color: #f39c12;
    }

    .nav-link i {
        width: 20px;
        margin-right: 0.75rem;
        text-align: center;
    }

    /* 主内容区 */
    .main-content {
        flex: 1;
        padding: 2rem;
        overflow-y: auto;
        max-width: calc(100vw - 260px); /* 确保内容不会溢出 */
    }

    /* 页面头部 */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
    }

    .header-left h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .header-left p {
        color: #7f8c8d;
        font-size: 0.95rem;
    }

    .header-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap; /* 允许换行 */
    }

    /* 卡片样式 */
    .content-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
        background: #fafbfc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h3 {
        margin: 0;
        color: #2c3e50;
        font-weight: 600;
    }

    .card-actions {
        display: flex;
        gap: 0.5rem;
    }

    /* 按钮样式 */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
        background: none; /* 确保默认背景是透明的 */
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn-primary {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        border-color: #2980b9;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2980b9, #21618c);
        color: white;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        border-color: #6c757d;
    }

    .btn-secondary:hover {
        background: #5a6268;
        color: white;

    }

    .btn-success {
        background: linear-gradient(135deg, #27ae60, #229954);
        color: white;
        border-color: #229954;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #229954, #1e8449);
        color: white;
    }

    .btn-info {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border-color: #138496;
    }

    .btn-info:hover {
        background: linear-gradient(135deg, #138496, #117a8b);
        color: white;
    }

    .btn-warning {
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: white;
        border-color: #e67e22;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, #e67e22, #d35400);
        color: white;
    }

    .btn-danger {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        border-color: #c0392b;
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #c0392b, #a93226);
        color: white;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid #6c757d;
        color: #6c757d;
    }

    .btn-outline:hover {
        background: #6c757d;
        color: white;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }

    .btn-lg {
        padding: 0.875rem 1.75rem;
        font-size: 1rem;
    }

    /* 表单样式 */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #495057;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
        background: white;
    }

    .form-control:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1rem;
    }

    .form-text {
        color: #6c757d;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .required {
        color: #e74c3c;
    }

    /* 表格样式 */
    .table-responsive {
        overflow-x: auto;
        border-radius: 10px;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }

    .data-table th {
        background: #f8f9fa;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        position: sticky; /* 表头粘性定位 */
        top: 0;
        z-index: 10;
    }

    .data-table td {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .data-table tr:hover {
        background: #f8f9fa;
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }

    /* 分页样式 */
    .pagination-wrapper {
        padding: 1.5rem;
        border-top: 1px solid #eee;
        background: #fafbfc;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        align-items: center;
    }

    .page-btn {
        padding: 0.5rem 0.75rem;
        border: 1px solid #dee2e6;
        background: white;
        color: #495057;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
        min-width: 40px;
        text-align: center;
    }

    .page-btn:hover {
        background: #e9ecef;
        border-color: #adb5bd;
        color: #495057;
    }

    .page-btn.active {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }

    .page-btn:disabled {
        background: #f8f9fa;
        color: #6c757d;
        cursor: not-allowed;
    }

    /* 状态标签 */
    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
        text-align: center;
        display: inline-block;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .badge-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #b8daff;
    }

    .badge-secondary {
        background: #e2e3e5;
        color: #383d41;
        border: 1px solid #d6d8db;
    }

    /* 提示消息 */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border-left: 4px solid;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left-color: #27ae60;
    }

    .alert-error, /* 与 alert-danger 相同 */
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left-color: #e74c3c;
    }

    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border-left-color: #f39c12;
    }

    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border-left-color: #17a2b8;
    }

    /* 空状态 */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
        color: #adb5bd;
    }

    .empty-state p {
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    /* 加载状态 */
    .loading {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: #6c757d;
    }

    .loading::before {
        content: '';
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 0.5rem;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* 工具类 */
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .text-muted { color: #6c757d; }
    .text-primary { color: #3498db; }
    .text-success { color: #27ae60; }
    .text-warning { color: #f39c12; }
    .text-danger { color: #e74c3c; }

    .d-flex { display: flex; }
    .d-block { display: block; }
    .d-inline { display: inline; }
    .d-inline-block { display: inline-block; }
    .d-none { display: none; }

    .justify-content-center { justify-content: center; }
    .justify-content-between { justify-content: space-between; }
    .justify-content-end { justify-content: flex-end; }
    .align-items-center { align-items: center; }
    .align-items-start { align-items: flex-start; }
    .align-items-end { align-items: flex-end; }

    .mt-1 { margin-top: 0.25rem; }
    .mt-2 { margin-top: 0.5rem; }
    .mt-3 { margin-top: 1rem; }
    .mt-4 { margin-top: 1.5rem; }
    .mt-5 { margin-top: 3rem; }

    .mb-1 { margin-bottom: 0.25rem; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mb-3 { margin-bottom: 1rem; }
    .mb-4 { margin-bottom: 1.5rem; }
    .mb-5 { margin-bottom: 3rem; }

    .ml-1 { margin-left: 0.25rem; }
    .ml-2 { margin-left: 0.5rem; }
    .ml-3 { margin-left: 1rem; }
    .ml-auto { margin-left: auto; }

    .mr-1 { margin-right: 0.25rem; }
    .mr-2 { margin-right: 0.5rem; }
    .mr-3 { margin-right: 1rem; }
    .mr-auto { margin-right: auto; }

    .p-1 { padding: 0.25rem; }
    .p-2 { padding: 0.5rem; }
    .p-3 { padding: 1rem; }
    .p-4 { padding: 1.5rem; }
    .p-5 { padding: 3rem; }

    .w-100 { width: 100%; }
    .h-100 { height: 100%; }

    /* 顶部导航栏 (在 admin_header.php 中) */
    .top-navbar {
        background: white;
        padding: 0.75rem 1.5rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        flex-shrink: 0;
    }

    .navbar-brand {
        font-weight: 700;
        color: #2c3e50;
        text-decoration: none;
        font-size: 1.1rem;
    }

    .navbar-nav {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .nav-user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #495057;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.8rem;
    }

    /* 响应式设计 */
    @media (max-width: 1200px) {
        .main-content {
            padding: 1.5rem;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .header-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }

    @media (max-width: 768px) {
        .admin-container {
            flex-direction: column;
        }
        
        .sidebar {
            width: 100%;
            height: auto;
        }
        
        .main-content {
            max-width: 100vw; /* 在小屏幕上取消固定宽度 */
            padding: 1rem;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .header-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .header-actions .btn {
            width: 100%;
            justify-content: center;
        }
        
        .data-table {
            font-size: 0.85rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem 0.5rem;
        }
        
        .navbar-nav {
            flex-direction: column;
            gap: 0.5rem;
        }
    }

    @media (max-width: 480px) {
        .main-content {
            padding: 0.75rem;
        }
        
        .page-header h1 {
            font-size: 1.4rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .content-card {
            border-radius: 8px;
        }
    }

    /* 打印样式 */
    @media print {
        .sidebar,
        .header-actions,
        .pagination-wrapper,
        .admin-footer,
        .top-navbar,
        .alert,
        .modal,
        .toast-container {
            display: none !important;
        }
        
        body {
            background: white !important;
            color: black !important;
        }

        .main-content {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .content-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            page-break-inside: avoid; /* 防止内容卡片被分页切断 */
        }

        table, th, td {
            border-color: #bbb !important;
        }

        .data-table th {
            background: #f0f0f0 !important;
            color: #333 !important;
        }
    }