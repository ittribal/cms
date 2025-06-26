
<?php
// ==================== 安装说明 README.md ====================
?>
<script type="text/markdown">
# CMS网站管理系统 - 完整安装指南

## 🚀 系统简介

这是一个功能完整的PHP + MySQL内容管理系统，包含：

### ✨ 核心功能
- **前台展示**: 现代化响应式设计，技术文档展示
- **后台管理**: 完整的管理界面，支持多用户权限管理
- **内容管理**: 文章、分类、媒体文件管理
- **用户系统**: 多角色权限控制
- **系统设置**: 灵活的系统配置
- **安全机制**: CSRF防护、SQL注入防护、XSS防护

### 🛠️ 技术栈
- **前端**: HTML5, CSS3, JavaScript, 响应式设计
- **后端**: PHP 7.4+, PDO数据库操作
- **数据库**: MySQL 5.7+ / MariaDB 10.3+
- **编辑器**: CKEditor 5 富文本编辑
- **安全**: 密码哈希、CSRF令牌、输入验证

## 📋 系统要求

### 服务器环境
- **PHP**: 7.4 或更高版本
- **MySQL**: 5.7 或更高版本 / MariaDB 10.3+
- **Web服务器**: Apache 2.4+ / Nginx 1.18+
- **PHP扩展**: PDO, PDO_MySQL, mbstring, fileinfo, gd

### 推荐配置
```
memory_limit = 256M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 300
```

## 📦 安装步骤

### 1. 下载和解压
```bash
# 下载源码到网站根目录
cd /var/www/html
# 解压所有文件
```

### 2. 数据库配置
```sql
-- 创建数据库
CREATE DATABASE cms_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户并授权
CREATE USER 'cms_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON cms_website.* TO 'cms_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. 导入数据库结构
执行代码中的SQL语句创建所有表结构和初始数据

### 4. 配置文件设置
编辑 `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'cms_website';
private $username = 'cms_user';
private $password = 'your_password';
```

### 5. 设置文件权限
```bash
# 设置上传目录权限
chmod 755 uploads/
chmod 755 uploads/system/

# 设置配置文件权限
chmod 644 config/*.php
```

### 6. 访问系统
- **前台地址**: http://yourdomain.com/
- **后台地址**: http://yourdomain.com/admin/
- **默认管理员**: 
  - 用户名: `admin`
  - 密码: `admin123`

## 🔒 首次登录后的安全设置

### 1. 更改默认密码
- 登录后台后立即修改管理员密码
- 密码要求：至少8位，包含大小写字母和数字

### 2. 配置系统设置
- 设置网站名称和描述
- 配置邮件服务器（如需要）
- 调整安全设置

### 3. 创建新用户
- 为不同的管理员创建单独账户
- 根据需要分配相应权限

## 🎯 使用指南

### 管理员权限级别

#### 超级管理员 (Role ID: 1)
- 拥有所有权限
- 用户管理
- 系统设置
- 数据备份

#### 内容管理员 (Role ID: 2)
- 内容管理 (增删改查)
- 媒体文件管理
- 分类管理

#### 编辑 (Role ID: 3)
- 创建和编辑文章
- 查看内容列表

### 内容管理流程

#### 1. 分类管理
```
后台 → 分类管理 → 添加分类
- 设置分类名称
- 添加描述
- 系统自动生成URL友好的slug
```

#### 2. 文章管理
```
后台 → 文章管理 → 写文章
- 输入标题和内容
- 选择分类
- 设置SEO信息
- 选择发布状态
```

#### 3. 媒体管理
```
后台 → 媒体管理 → 上传文件
- 支持图片格式：JPG, PNG, GIF, WebP
- 自动生成缩略图
- 提供文件管理界面
```

## 🔧 高级配置

### 1. URL重写 (.htaccess)
```apache
RewriteEngine On
RewriteBase /

# 后台访问
RewriteRule ^admin/$ admin/index.php [L]
RewriteRule ^admin/([^/]+)/$ admin/$1/index.php [L]

# 文章页面
RewriteRule ^article/([^/]+)/?$ article.php?slug=$1 [L,QSA]

# 分类页面  
RewriteRule ^category/([^/]+)/?$ category.php?slug=$1 [L,QSA]

# 禁止直接访问敏感文件
RewriteRule ^(config|includes)/ - [F,L]
```

### 2. Nginx配置
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /admin {
        try_files $uri $uri/ /admin/index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\. {
        deny all;
    }
}
```

### 3. 环境变量配置
创建 `.env` 文件：
```env
DB_HOST=localhost
DB_NAME=cms_website
DB_USER=cms_user
DB_PASS=your_password

SITE_URL=https://yourdomain.com
ADMIN_EMAIL=admin@yourdomain.com

SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=noreply@yourdomain.com
SMTP_PASS=smtp_password

UPLOAD_MAX_SIZE=32M
SESSION_TIMEOUT=3600
```

## 🛡️ 安全最佳实践

### 1. 服务器安全
```bash
# 定期更新系统
sudo apt update && sudo apt upgrade

# 配置防火墙
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable

# 设置SSL证书
sudo certbot --nginx -d yourdomain.com
```

### 2. PHP安全配置
```ini
; 隐藏PHP版本
expose_php = Off

; 禁用危险函数
disable_functions = exec,passthru,shell_exec,system,proc_open,popen

; 限制文件上传
file_uploads = On
upload_max_filesize = 32M
max_file_uploads = 20

; 错误报告
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

### 3. MySQL安全
```sql
-- 删除匿名用户
DELETE FROM mysql.user WHERE User='';

-- 删除测试数据库
DROP DATABASE IF EXISTS test;

-- 设置强密码策略
SET GLOBAL validate_password.policy=MEDIUM;
```

## 📊 性能优化

### 1. 数据库优化
```sql
-- 为文章表创建索引
CREATE INDEX idx_articles_status_published ON articles(status, published_at);
CREATE INDEX idx_articles_category ON articles(category_id);
CREATE FULLTEXT INDEX idx_articles_search ON articles(title, content);

-- 为日志表创建索引
CREATE INDEX idx_logs_created ON admin_logs(created_at);
CREATE INDEX idx_logs_user_action ON admin_logs(user_id, action);
```

### 2. 缓存策略
```php
// 在includes/functions.php中添加缓存函数
function cache_get($key) {
    $cache_file = "cache/" . md5($key) . ".cache";
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 3600)) {
        return unserialize(file_get_contents($cache_file));
    }
    return false;
}

function cache_set($key, $data) {
    $cache_dir = "cache/";
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    $cache_file = $cache_dir . md5($key) . ".cache";
    file_put_contents($cache_file, serialize($data));
}
```

### 3. 图片优化
```php
// 图片压缩函数
function compress_image($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    
    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        default:
            return false;
    }
    
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    return true;
}
```

## 🔄 数据备份与恢复

### 1. 自动备份脚本
```bash
#!/bin/bash
# backup.sh - 定期备份脚本

BACKUP_DIR="/var/backups/cms"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="cms_website"
DB_USER="cms_user"
DB_PASS="your_password"

# 创建备份目录
mkdir -p $BACKUP_DIR

# 数据库备份
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# 文件备份
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/html/uploads

# 删除30天前的备份
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "备份完成: $DATE"
```

### 2. 设置定时任务
```bash
# 编辑crontab
crontab -e

# 每天凌晨2点自动备份
0 2 * * * /path/to/backup.sh

# 每周清理过期日志
0 3 * * 0 mysql -ucms_user -pyour_password cms_website -e "DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

## 🐛 常见问题解决

### 1. 上传文件失败
```
检查项目：
- upload_max_filesize 和 post_max_size 设置
- uploads/ 目录权限 (755)
- 磁盘空间是否足够
- PHP error_log 查看具体错误
```

### 2. 登录后立即退出
```
可能原因：
- session.save_path 权限问题
- 时区设置不正确
- Cookie 设置问题
解决方案：
- 检查PHP session配置
- 设置正确的时区
- 确保HTTPS设置正确
```

### 3. 数据库连接失败
```
检查项目：
- 数据库用户权限
- 防火墙设置
- MySQL服务状态
- 配置文件中的连接信息
```

### 4. 页面显示空白
```
调试步骤：
1. 开启PHP错误显示
2. 检查PHP error_log
3. 验证文件权限
4. 确认所有必需的PHP扩展已安装
```

## 📚 开发文档

### API接口说明
系统提供REST风格的API接口，支持JSON格式数据交互。

#### 认证
```php
// 所有API请求需要在请求头中包含认证信息
Authorization: Bearer {access_token}
```

#### 文章API
```php
// 获取文章列表
GET /api/articles
参数: page, per_page, category, status

// 获取单篇文章
GET /api/articles/{id}

// 创建文章
POST /api/articles
Body: {title, content, category_id, status}

// 更新文章
PUT /api/articles/{id}
Body: {title, content, category_id, status}

// 删除文章
DELETE /api/articles/{id}
```

### 自定义主题开发
```php
// 主题目录结构
themes/
├── default/
│   ├── index.php          # 首页模板
│   ├── article.php        # 文章页模板
│   ├── category.php       # 分类页模板
│   ├── style.css          # 主题样式
│   └── functions.php      # 主题函数
```

### 插件开发接口
```php
// 注册插件钩子
add_action('article_publish', 'my_plugin_function');

function my_plugin_function($article_id) {
    // 插件逻辑
}
```

## 🆕 版本更新

### 当前版本: v1.0.0
- 完整的CMS功能
- 多用户权限管理
- 响应式前台设计
- 安全机制完善

### 更新计划
- [ ] 插件系统
- [ ] 主题系统
- [ ] REST API接口
- [ ] 多语言支持
- [ ] 评论系统
- [ ] 搜索功能优化

## 📞 技术支持

如有任何问题，请通过以下方式获取帮助：
- 查看系统日志 (admin/system/logs.php)
- 检查错误信息并搜索解决方案
- 参考PHP和MySQL官方文档

## 📝 许可证

本项目采用MIT许可证，您可以自由使用、修改和分发。

---

**祝您使用愉快！** 🎉
</script>