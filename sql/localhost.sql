-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-06-27 00:12:45
-- 服务器版本： 5.7.43-log
-- PHP 版本： 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `10_10_10_99_1100`
--
CREATE DATABASE IF NOT EXISTS `10_10_10_99_1100` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `10_10_10_99_1100`;

-- --------------------------------------------------------

--
-- 表的结构 `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, '用户退出', '主动退出', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 01:56:00'),
(2, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 02:57:34'),
(3, 1, '用户退出', '主动退出', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 02:59:57'),
(4, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 03:00:01'),
(5, 1, '用户退出', '主动退出', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 03:29:02'),
(6, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 03:29:05'),
(7, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 04:41:53'),
(8, 1, '用户退出', '主动退出', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 05:30:56'),
(9, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 05:30:58'),
(10, 1, '用户退出', '主动退出', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 06:31:51'),
(11, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 06:31:53'),
(12, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 07:41:31'),
(13, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 11:13:20'),
(14, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 14:42:35'),
(15, 1, '用户登录', '登录成功', NULL, 'null', '10.10.10.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-26 14:47:52');

-- --------------------------------------------------------

--
-- 表的结构 `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `featured_image` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `views` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `articles`
--

INSERT INTO `articles` (`id`, `title`, `slug`, `content`, `excerpt`, `category_id`, `author_id`, `status`, `featured_image`, `meta_title`, `meta_description`, `views`, `created_at`, `updated_at`, `published_at`) VALUES
(1, '欢迎使用CMS系统', 'welcome-to-cms', '<h2>欢迎使用我们的CMS系统！</h2><p>这是一个功能强大、易于使用的内容管理系统。</p><p>主要特性包括：</p><ul><li>用户权限管理</li><li>文章发布系统</li><li>分类管理</li><li>SEO优化</li><li>响应式设计</li></ul>', '欢迎使用我们的CMS系统！这里介绍了系统的主要特性和使用方法。', NULL, 1, 'published', 'uploads/images/1750870894_1514.png', '', '', 3, '2025-06-16 08:48:36', '2025-06-25 17:01:34', '2025-06-16 16:48:36'),
(38, '11111', '1', '<p>11111</p>', '11111', NULL, 1, 'published', 'uploads/images/1750870884_6647.png', '', '', 0, '2025-06-25 16:19:25', '2025-06-25 17:01:24', '2025-06-26 00:19:25'),
(39, '22222', '2', '<p>22222</p>', '22222', NULL, 1, 'published', 'uploads/images/1750870873_1308.png', '', '', 0, '2025-06-25 16:19:33', '2025-06-25 17:01:13', '2025-06-26 00:19:33'),
(40, '33333', '3', '<p>33333</p>', '33333', NULL, 1, 'published', 'uploads/images/1750870958_4618.png', '', '', 0, '2025-06-25 16:19:41', '2025-06-25 17:04:38', '2025-06-26 00:19:41'),
(41, '44444', '4', '<p>44444</p>', '44444', NULL, 1, 'published', 'uploads/images/1750870860_6191.png', '', '', 0, '2025-06-25 16:19:48', '2025-06-25 17:01:00', '2025-06-26 00:19:48'),
(42, '55555', '5', '<p>55555</p>', '55555', NULL, 1, 'published', 'uploads/images/1750870847_9522.png', '', '', 0, '2025-06-25 16:19:54', '2025-06-25 17:00:47', '2025-06-26 00:19:54'),
(43, '66666', '6', '<p>66666</p>', '66666', NULL, 1, 'published', 'uploads/images/1750870824_7158.png', '', '', 0, '2025-06-25 16:20:01', '2025-06-25 17:00:24', '2025-06-26 00:20:01');

-- --------------------------------------------------------

--
-- 表的结构 `article_drafts`
--

CREATE TABLE `article_drafts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文章标题',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文章内容',
  `excerpt` text COLLATE utf8mb4_unicode_ci COMMENT '文章摘要',
  `category_id` int(11) DEFAULT NULL COMMENT '分类ID',
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签',
  `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SEO标题',
  `meta_description` text COLLATE utf8mb4_unicode_ci COMMENT 'SEO描述',
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SEO关键词',
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '特色图片',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章草稿表';

-- --------------------------------------------------------

--
-- 表的结构 `article_tags`
--

CREATE TABLE `article_tags` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL COMMENT '文章ID',
  `tag_id` int(11) NOT NULL COMMENT '标签ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章标签关联表';

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `to_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '收件人邮箱',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮件主题',
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮件内容',
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '发送状态',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `sent_at` datetime DEFAULT NULL COMMENT '发送时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件发送日志表';

-- --------------------------------------------------------

--
-- 表的结构 `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP地址',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT '用户代理',
  `success` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否成功',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '失败原因',
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录记录表';

--
-- 转存表中的数据 `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `user_agent`, `success`, `reason`, `attempted_at`) VALUES
(1, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-25 17:13:14'),
(2, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-25 17:13:30'),
(3, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-25 17:13:35'),
(4, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-25 17:16:24'),
(5, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-25 17:16:30'),
(6, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-26 01:56:03'),
(7, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-26 01:56:51'),
(8, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-26 02:16:53'),
(9, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-26 02:16:59'),
(10, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-26 02:51:09'),
(11, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-26 02:51:18'),
(12, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-26 02:52:43'),
(13, 'admin', '10.10.10.123', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 0, NULL, '2025-06-26 02:53:08');

-- --------------------------------------------------------

--
-- 表的结构 `media_files`
--

CREATE TABLE `media_files` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '通知类型',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '通知标题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '通知内容',
  `data` json DEFAULT NULL COMMENT '通知数据',
  `read_at` datetime DEFAULT NULL COMMENT '阅读时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表';

-- --------------------------------------------------------

--
-- 表的结构 `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `permissions`, `description`, `created_at`) VALUES
(1, 'super_admin', 'super-admin', '[\"*\"]', '拥有系统所有权限', '2025-06-16 08:48:36'),
(2, 'admin', 'admin', '[\"users.view\", \"users.create\", \"users.edit\", \"users.delete\", \"users.assign_role\", \"content.view\", \"content.create\", \"content.edit\", \"content.delete\", \"content.publish\", \"categories.view\", \"categories.create\", \"categories.edit\", \"categories.delete\", \"tags.view\", \"tags.create\", \"tags.edit\", \"tags.delete\", \"media.view\", \"media.upload\", \"media.edit\", \"media.delete\", \"comments.view\", \"comments.moderate\", \"comments.reply\", \"comments.delete\", \"system.settings\", \"system.logs\", \"system.backup\", \"system.cache\", \"emails.view\", \"emails.send\"]', '拥有大部分管理权限', '2025-06-16 08:48:36'),
(3, 'editor', 'editor', '[\"content.view\", \"content.create\", \"content.edit\", \"content.publish\", \"categories.view\", \"categories.edit\", \"tags.view\", \"tags.create\", \"tags.edit\", \"media.view\", \"media.upload\", \"media.edit\", \"comments.view\", \"comments.moderate\", \"comments.reply\"]', '可以管理文章、分类、标签和审核评论', '2025-06-16 08:48:36'),
(4, 'author', 'author', '[\"content.view\", \"content.create\", \"content.edit_own\", \"content.delete_own\", \"media.view\", \"media.upload\", \"comments.view\"]', '可以创建和编辑自己的文章，上传媒体', '2025-06-16 08:48:36'),
(5, 'subscriber', 'subscriber', '[\"profile.view\", \"profile.edit\"]', '只能查看已发布内容和管理个人资料', '2025-06-16 08:48:36');

-- --------------------------------------------------------

--
-- 表的结构 `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext,
  `setting_type` enum('text','textarea','select','checkbox','file') DEFAULT 'text',
  `description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'CMS管理系统', 'text', '网站名称', '2025-06-16 03:19:31'),
(2, 'site_description', '基于PHP开发的内容管理系统', 'textarea', '网站描述', '2025-06-17 03:58:00'),
(3, 'admin_email', 'admin@example.com', 'text', '管理员邮箱', '2025-06-17 03:58:00'),
(4, 'items_per_page', '20', 'text', '每页显示条目数', '2025-06-16 03:19:31'),
(5, 'allow_registration', '0', 'checkbox', '允许用户注册', '2025-06-16 03:19:31'),
(6, 'site_logo', '', 'file', '网站Logo', '2025-06-16 03:19:31'),
(7, 'maintenance_mode', '0', 'checkbox', '维护模式', '2025-06-16 03:19:31'),
(20, 'email_smtp_host', 'smtp.gmail.com', 'text', 'SMTP服务器地址', '2025-06-25 00:00:00'),
(21, 'email_smtp_port', '587', 'text', 'SMTP端口', '2025-06-25 00:00:00'),
(22, 'email_smtp_username', '', 'text', 'SMTP用户名', '2025-06-25 00:00:00'),
(23, 'email_smtp_password', '', 'text', 'SMTP密码', '2025-06-25 00:00:00'),
(24, 'email_smtp_secure', 'tls', 'select', 'SMTP加密方式 (tls/ssl/无)', '2025-06-25 00:00:00'),
(25, 'email_from_email', 'noreply@yourdomain.com', 'text', '发件人邮箱', '2025-06-25 00:00:00'),
(26, 'email_from_name', '您的网站名称', 'text', '发件人名称', '2025-06-25 00:00:00');

-- --------------------------------------------------------

--
-- 表的结构 `system_cache`
--

CREATE TABLE `system_cache` (
  `cache_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '缓存键',
  `cache_value` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '缓存值',
  `cache_type` enum('string','json','serialized') COLLATE utf8mb4_unicode_ci DEFAULT 'string' COMMENT '缓存类型',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统缓存表';

-- --------------------------------------------------------

--
-- 表的结构 `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标签名称',
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL别名',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '标签描述',
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#3498db' COMMENT '标签颜色',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `real_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '真实姓名',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系电话',
  `bio` text COLLATE utf8mb4_unicode_ci COMMENT '个人简介',
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '个人网站',
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '所在地',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '性别',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` timestamp NULL DEFAULT NULL COMMENT '上次登录时间',
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '上次登录IP',
  `last_activity_at` timestamp NULL DEFAULT NULL COMMENT '上次活动时间',
  `last_activity_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '上次活动IP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role_id`, `status`, `avatar`, `real_name`, `phone`, `bio`, `website`, `location`, `birthday`, `gender`, `created_at`, `updated_at`, `last_login_at`, `last_login_ip`, `last_activity_at`, `last_activity_ip`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$gkGJ03Rk48PmFep9mM/XqebC5WkdSuRtq3Zp1UIYa1ZFE54UgPnOy', 1, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-16 08:48:36', '2025-06-26 16:03:22', '2025-06-26 14:47:52', '10.10.10.103', '2025-06-26 16:03:22', '10.10.10.103');

-- --------------------------------------------------------

--
-- 表的结构 `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Session ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP地址',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT '用户代理',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Session数据',
  `last_activity` int(11) NOT NULL COMMENT '最后活动时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户会话表';

--
-- 转储表的索引
--

--
-- 表的索引 `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- 表的索引 `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_author` (`author_id`);
ALTER TABLE `articles` ADD FULLTEXT KEY `idx_search` (`title`,`content`);

--
-- 表的索引 `article_drafts`
--
ALTER TABLE `article_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- 表的索引 `article_tags`
--
ALTER TABLE `article_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_article_tag` (`article_id`,`tag_id`),
  ADD KEY `idx_article_id` (`article_id`),
  ADD KEY `idx_tag_id` (`tag_id`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- 表的索引 `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_to_email` (`to_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 表的索引 `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`),
  ADD KEY `idx_success` (`success`);

--
-- 表的索引 `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_mime_type` (`mime_type`);

--
-- 表的索引 `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_read_at` (`read_at`);

--
-- 表的索引 `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- 表的索引 `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- 表的索引 `system_cache`
--
ALTER TABLE `system_cache`
  ADD PRIMARY KEY (`cache_key`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- 表的索引 `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role_id` (`role_id`);

--
-- 表的索引 `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用表AUTO_INCREMENT `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- 使用表AUTO_INCREMENT `article_drafts`
--
ALTER TABLE `article_drafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `article_tags`
--
ALTER TABLE `article_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `media_files`
--
ALTER TABLE `media_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- 使用表AUTO_INCREMENT `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 限制导出的表
--

--
-- 限制表 `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `fk_admin_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- 限制表 `article_drafts`
--
ALTER TABLE `article_drafts`
  ADD CONSTRAINT `fk_article_drafts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
