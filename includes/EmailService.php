<?php
// includes/EmailService.php - 邮件服务类 (使用 PHPMailer)

// 引入 PHPMailer 的核心文件
// ABSPATH 常量在 config.php 中定义，确保路径正确
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException; // 为 PHPMailer 的异常类起别名，避免与 PHP 内置的 Exception 冲突

require_once ABSPATH . 'includes/PHPMailer/src/PHPMailer.php';
require_once ABSPATH . 'includes/PHPMailer/src/SMTP.php';
require_once ABSPATH . 'includes/PHPMailer/src/Exception.php';

// 确保 Database 类已加载 (由 config.php 中的 spl_autoload_register 处理)
// require_once ABSPATH . 'includes/Database.php'; // 实际无需手动引入，spl_autoload_register 会处理

class EmailService {
    private $db;
    private $config; // 存储邮件配置
    private static $instance = null; // 单例模式实例

    // 构造函数
    public function __construct() {
        $this->db = Database::getInstance(); // 获取数据库单例
        $this->loadConfig(); // 加载邮件配置
    }

    // 获取 EmailService 类的唯一实例（单例模式的入口）
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // 从 site_settings 表加载邮件配置
    private function loadConfig() {
        // 从 site_settings 表加载邮件相关设置，以及站点标题和管理员邮箱
        $settings_query = $this->db->fetchAll("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'email_%' OR setting_key IN ('site_title', 'admin_email')");
        $this->config = [];
        foreach ($settings_query as $setting) {
            $this->config[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // 默认配置（如果数据库中没有设置，则使用这些默认值）
        // 这里的键名需要与你在 admin/system/settings.php 中邮件设置表单的 name 属性一致
        $defaults = [
            'email_smtp_host' => 'smtp.example.com',
            'email_smtp_port' => '587',
            'email_smtp_username' => '',
            'email_smtp_password' => '',
            'email_smtp_secure' => 'tls', // tls 或 ssl 或空
            'email_from_email' => MAIL_FROM_EMAIL, // 从 config.php 获取默认值
            'email_from_name' => MAIL_FROM_NAME,   // 从 config.php 获取默认值
            'site_title' => SITE_TITLE,             // 从 config.php 获取默认值
            'admin_email' => 'admin@example.com'    // 默认管理员邮箱
        ];
        
        // 合并默认值和数据库加载的配置
        $this->config = array_merge($defaults, $this->config);
    }
    
    /**
     * 发送邮件
     * @param string $toEmail 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $body 邮件内容 (可以是HTML)
     * @param bool $isHtml 是否为HTML邮件
     * @param array $attachments 附件路径数组
     * @param string|null $fromEmail 发件人邮箱，如果为null则使用配置中的默认发件人邮箱
     * @param string|null $fromName 发件人名称，如果为null则使用配置中的默认发件人名称
     * @return bool 邮件是否成功发送
     */
    public function sendMail($toEmail, $subject, $body, $isHtml = true, $attachments = [], $fromEmail = null, $fromName = null) {
        $mail = new PHPMailer(true); // 开启异常模式，PHPMailer 会抛出 Exception
        $logStatus = 'pending';      // 初始日志状态
        $errorMessage = null;        // 错误信息

        try {
            // SMTP 配置
            $mail->isSMTP();
            $mail->Host       = $this->config['email_smtp_host'];
            $mail->SMTPAuth   = true; // 启用 SMTP 认证
            $mail->Username   = $this->config['email_smtp_username'];
            $mail->Password   = $this->config['email_smtp_password'];
            // SMTP 加密方式
            $mail->SMTPSecure = $this->config['email_smtp_secure'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : ($this->config['email_smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : '');
            $mail->Port       = $this->config['email_smtp_port'];
            
            // 邮件内容设置
            $mail->CharSet    = 'UTF-8'; // 设置字符编码
            $mail->setFrom($fromEmail ?: $this->config['email_from_email'], $fromName ?: $this->config['email_from_name']); // 设置发件人
            $mail->addAddress($toEmail); // 添加收件人
            
            $mail->isHTML($isHtml); // 设置邮件内容是否为 HTML
            $mail->Subject = $subject; // 邮件主题
            $mail->Body    = $body;    // 邮件正文
            
            // 添加附件
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }

            $mail->send(); // 发送邮件
            $logStatus = 'sent'; // 发送成功
            return true;

        } catch (PHPMailerException $e) {
            // 捕获 PHPMailer 抛出的特定异常
            $errorMessage = "PHPMailer 错误: {$mail->ErrorInfo}";
            $logStatus = 'failed';
            error_log($errorMessage); // 记录详细错误信息到 PHP 错误日志
            return false;
        } catch (Exception $e) {
            // 捕获其他非 PHPMailer 的通用异常
            $errorMessage = "通用错误: {$e->getMessage()}";
            $logStatus = 'failed';
            error_log($errorMessage);
            return false;
        } finally {
            // 无论成功或失败，都记录邮件日志
            $this->logEmail($toEmail, $subject, $body, $logStatus, $errorMessage);
            // 如果邮件发送成功，且之前状态为 pending，则更新为 sent (确保只更新最新一条 pending 记录)
            if ($logStatus === 'sent') {
                $this->updateEmailStatus($toEmail, $subject, $logStatus);
            }
        }
    }
    
    /**
     * 发送系统通知邮件 (例如用户注册、密码重置、新文章发布等)
     * @param string $type 通知类型 (对应预定义的模板和主题)
     * @param string $toEmail 接收通知的邮箱 (可以是用户邮箱或管理员邮箱)
     * @param array $data 邮件模板中需要替换的数据
     * @return bool
     */
    public function sendSystemNotification($type, $toEmail, $data = []) {
        // 预定义的通知模板和主题
        $templates = [
            'user_registered' => [
                'subject' => '欢迎注册 - {site_title}',
                'template' => 'welcome_email',
            ],
            'password_reset' => [
                'subject' => '重置密码 - {site_title}',
                'template' => 'password_reset',
            ],
            'new_article_notification' => [ // 新文章发布通知管理员
                'subject' => '新文章发布: {article_title}',
                'template' => 'new_article_notification',
            ],
            'comment_notification' => [ // 新评论通知文章作者或管理员
                'subject' => '新评论通知: {article_title}',
                'template' => 'comment_notification',
            ]
        ];
        
        if (!isset($templates[$type])) {
            error_log("未知的邮件通知类型: $type");
            return false;
        }
        
        $templateData = $templates[$type];
        
        // 整合额外数据 (如站点标题和 URL) 到 $data 数组中，方便模板使用
        $mergedData = array_merge($data, [
            'site_title' => $this->config['site_title'],
            'site_url' => SITE_URL,
            'admin_email' => $this->config['admin_email']
        ]);

        // 替换邮件主题中的变量
        $subject = $this->replaceVariables($templateData['subject'], $mergedData);
        // 生成邮件正文 (通过加载 PHP 模板文件)
        $body = $this->generateEmailBody($templateData['template'], $mergedData);
        
        // 发送邮件
        return $this->sendMail($toEmail, $subject, $body, true);
    }
    
    /**
     * 从模板文件生成邮件内容
     * @param string $templateName 模板文件名 (例如 'welcome_email'，对应 templates/emails/welcome_email.php)
     * @param array $data 模板中需要用到的数据
     * @return string 生成的 HTML 邮件内容
     */
    private function generateEmailBody($templateName, $data) {
        $templatePath = ABSPATH . "templates/emails/{$templateName}.php";
        
        if (file_exists($templatePath)) {
            ob_start(); // 开启输出缓冲
            extract($data); // 将 $data 数组中的键值对导入为变量，供模板文件直接使用
            include $templatePath; // 包含模板文件，其输出会被缓冲
            return ob_get_clean(); // 获取缓冲内容并关闭缓冲
        }
        
        // 如果模板文件不存在，则使用一个通用默认模板
        return $this->getDefaultTemplate($data);
    }
    
    // 通用默认邮件模板 (HTML结构)
    private function getDefaultTemplate($data) {
        $siteTitle = $this->config['site_title'] ?? SITE_TITLE;
        $siteUrl = SITE_URL;
        $subject = esc_html($data['subject'] ?? '系统通知'); // 确保 HTML 安全
        $content = esc_html($data['content'] ?? '您收到了一条系统通知，请登录网站后台查看详情。'); // 确保 HTML 安全

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject}</title>
            <style>
                body { font-family: 'Helvetica Neue', 'Arial', sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background: #3498db; color: white; padding: 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .content p { margin-bottom: 15px; }
                .button { display: inline-block; padding: 10px 20px; margin-top: 20px; background: #3498db; color: #fff; text-decoration: none; border-radius: 5px; }
                .footer { padding: 20px; text-align: center; color: #777; font-size: 12px; border-top: 1px solid #eee; margin-top: 20px; }
                .footer a { color: #3498db; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$siteTitle}</h1>
                </div>
                <div class='content'>
                    <p>主题: {$subject}</p>
                    <p>{$content}</p>
                    <p>此邮件由系统自动发送，请勿回复。</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " <a href=\"{$siteUrl}\">{$siteTitle}</a>. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * 替换邮件模板中的变量
     * @param string $text 待替换的字符串
     * @param array $data 包含键值对的数组，键对应 {变量名}
     * @return string 替换后的字符串
     */
    private function replaceVariables($text, $data) {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) { // 只替换标量值（字符串、数字、布尔等）
                $text = str_replace('{' . $key . '}', (string)$value, $text); // 确保替换的值是字符串
            }
        }
        return $text;
    }
    
    /**
     * 记录邮件发送日志到 email_logs 表
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param string $status 发送状态 ('pending', 'sent', 'failed')
     * @param string|null $error 错误信息 (如果失败)
     */
    private function logEmail($to, $subject, $body, $status, $error = null) {
        $sql = "INSERT INTO email_logs (to_email, subject, body, status, error_message, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        try {
            $this->db->execute($sql, [$to, $subject, $body, $status, $error]);
        } catch (Exception $e) {
            // 如果邮件日志记录失败，仅记录到 PHP 错误日志，不影响邮件发送主流程
            error_log("邮件日志记录失败: " . $e->getMessage());
        }
    }
    
    /**
     * 更新 email_logs 表中邮件的状态
     * @param string $to 收件人邮箱
     * @param string $subject 邮件主题
     * @param string $status 新状态 ('sent', 'failed')
     */
    private function updateEmailStatus($to, $subject, $status) {
        // 更新最新一条与 $to 和 $subject 匹配且状态为 'pending' 的记录
        $sql = "UPDATE email_logs SET status = ?, sent_at = NOW() 
                WHERE to_email = ? AND subject = ? AND status = 'pending' 
                ORDER BY created_at DESC LIMIT 1"; 
        try {
            $this->db->execute($sql, [$status, $to, $subject]);
        } catch (Exception $e) {
            error_log("更新邮件日志状态失败: " . $e->getMessage());
        }
    }
    
    /**
     * 获取邮件发送统计数据
     * @param int $days 统计天数
     * @return array 统计结果 (total, sent, failed, pending)
     */
    public function getEmailStats($days = 30) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                FROM email_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stats = $this->db->fetchOne($sql, [$days]);
        return $stats ?: ['total' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0]; // 确保返回数组
    }
}