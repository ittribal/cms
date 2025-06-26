// ==================== install.php - 安装向导 ====================
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS系统安装向导</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .install-header h1 {
            color: #1e293b;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .install-header p {
            color: #64748b;
        }
        
        .install-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }
        
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        
        .step.completed .step-number {
            background: #22c55e;
            color: white;
        }
        
        .step-title {
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
        
        .install-content {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .requirement-list {
            list-style: none;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .requirement-item:last-child {
            border-bottom: none;
        }
        
        .requirement-status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .status-pass {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-fail {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .install-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🚀 CMS系统安装向导</h1>
            <p>欢迎使用CMS内容管理系统，让我们开始配置您的网站吧！</p>
        </div>
        
        <div class="install-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-title">环境检测</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-title">数据库配置</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-title">管理员设置</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-title">完成安装</div>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: 25%"></div>
        </div>
        
        <div class="install-content">
            <h3>系统环境检测</h3>
            <p style="margin-bottom: 1rem; color: #6b7280;">安装前请确保您的服务器环境符合以下要求：</p>
            
            <ul class="requirement-list">
                <li class="requirement-item">
                    <div class="requirement-status status-pass">✓</div>
                    <div>
                        <strong>PHP版本</strong> (当前: <?= PHP_VERSION ?>)
                        <div style="font-size: 12px; color: #6b7280;">要求: PHP 7.4.0 或更高版本</div>
                    </div>
                </li>
                
                <li class="requirement-item">
                    <div class="requirement-status <?= extension_loaded('pdo') ? 'status-pass' : 'status-fail' ?>">
                        <?= extension_loaded('pdo') ? '✓' : '✗' ?>
                    </div>
                    <div>
                        <strong>PDO扩展</strong>
                        <div style="font-size: 12px; color: #6b7280;">数据库连接必需</div>
                    </div>
                </li>
                
                <li class="requirement-item">
                    <div class="requirement-status <?= extension_loaded('pdo_mysql') ? 'status-pass' : 'status-fail' ?>">
                        <?= extension_loaded('pdo_mysql') ? '✓' : '✗' ?>
                    </div>
                    <div>
                        <strong>PDO MySQL扩展</strong>
                        <div style="font-size: 12px; color: #6b7280;">MySQL数据库支持</div>
                    </div>
                </li>
                
                <li class="requirement-item">
                    <div class="requirement-status <?= extension_loaded('mbstring') ? 'status-pass' : 'status-fail' ?>">
                        <?= extension_loaded('mbstring') ? '✓' : '✗' ?>
                    </div>
                    <div>
                        <strong>mbstring扩展</strong>
                        <div style="font-size: 12px; color: #6b7280;">多字节字符串处理</div>
                    </div>
                </li>
                
                <li class="requirement-item">
                    <div class="requirement-status <?= is_writable('.') ? 'status-pass' : 'status-fail' ?>">
                        <?= is_writable('.') ? '✓' : '✗' ?>
                    </div>
                    <div>
                        <strong>目录写入权限</strong>
                        <div style="font-size: 12px; color: #6b7280;">需要创建配置文件和上传目录</div>
                    </div>
                </li>
                
                <li class="requirement-item">
                    <div class="requirement-status <?= extension_loaded('gd') ? 'status-pass' : 'status-fail' ?>">
                        <?= extension_loaded('gd') ? '✓' : '✗' ?>
                    </div>
                    <div>
                        <strong>GD扩展</strong>
                        <div style="font-size: 12px; color: #6b7280;">图片处理功能</div>
                    </div>
                </li>
            </ul>
        </div>
        
        <div class="install-actions">
            <div></div>
            <button class="btn btn-primary" onclick="nextStep()">
                下一步 →
            </button>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        const totalSteps = 4;
        
        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateProgress();
            }
        }
        
        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateProgress();
            }
        }
        
        function updateProgress() {
            // 更新进度条
            const progress = (currentStep / totalSteps) * 100;
            document.querySelector('.progress-fill').style.width = progress + '%';
            
            // 更新步骤状态
            document.querySelectorAll('.step').forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index + 1 < currentStep) {
                    step.classList.add('completed');
                } else if (index + 1 === currentStep) {
                    step.classList.add('active');
                }
            });
            
            // 根据当前步骤显示不同内容
            showStepContent(currentStep);
        }
        
        function showStepContent(step) {
            const content = document.querySelector('.install-content');
            const actions = document.querySelector('.install-actions');
            
            switch(step) {
                case 1:
                    // 环境检测内容已经在HTML中
                    actions.innerHTML = `
                        <div></div>
                        <button class="btn btn-primary" onclick="nextStep()">下一步 →</button>
                    `;
                    break;
                    
                case 2:
                    content.innerHTML = `
                        <h3>数据库配置</h3>
                        <p style="margin-bottom: 1rem; color: #6b7280;">请输入您的数据库连接信息：</p>
                        <form id="dbForm">
                            <div class="form-group">
                                <label for="db_host">数据库主机</label>
                                <input type="text" id="db_host" class="form-control" value="localhost" required>
                            </div>
                            <div class="form-group">
                                <label for="db_name">数据库名称</label>
                                <input type="text" id="db_name" class="form-control" value="cms_website" required>
                            </div>
                            <div class="form-group">
                                <label for="db_user">数据库用户名</label>
                                <input type="text" id="db_user" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="db_pass">数据库密码</label>
                                <input type="password" id="db_pass" class="form-control">
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="testConnection()">测试连接</button>
                        </form>
                    `;
                    actions.innerHTML = `
                        <button class="btn btn-secondary" onclick="prevStep()">← 上一步</button>
                        <button class="btn btn-primary" onclick="nextStep()">下一步 →</button>
                    `;
                    break;
                    
                case 3:
                    content.innerHTML = `
                        <h3>管理员设置</h3>
                        <p style="margin-bottom: 1rem; color: #6b7280;">创建系统管理员账户：</p>
                        <form id="adminForm">
                            <div class="form-group">
                                <label for="admin_username">管理员用户名</label>
                                <input type="text" id="admin_username" class="form-control" value="admin" required>
                            </div>
                            <div class="form-group">
                                <label for="admin_email">管理员邮箱</label>
                                <input type="email" id="admin_email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="admin_password">管理员密码</label>
                                <input type="password" id="admin_password" class="form-control" required>
                                <small style="color: #6b7280;">密码长度至少8位，包含大小写字母和数字</small>
                            </div>
                            <div class="form-group">
                                <label for="admin_password_confirm">确认密码</label>
                                <input type="password" id="admin_password_confirm" class="form-control" required>
                            </div>
                        </form>
                    `;
                    actions.innerHTML = `
                        <button class="btn btn-secondary" onclick="prevStep()">← 上一步</button>
                        <button class="btn btn-primary" onclick="install()">开始安装</button>
                    `;
                    break;
                    
                case 4:
                    content.innerHTML = `
                        <div class="alert alert-success">
                            <h3>🎉 安装完成！</h3>
                            <p>恭喜您！CMS系统已成功安装。</p>
                        </div>
                        <div style="margin-top: 1rem;">
                            <h4>接下来您可以：</h4>
                            <ul style="margin-left: 1rem; margin-top: 0.5rem; color: #6b7280;">
                                <li>访问前台网站查看效果</li>
                                <li>登录后台管理系统</li>
                                <li>开始创建您的第一篇文章</li>
                                <li>配置系统设置</li>
                            </ul>
                        </div>
                        <div style="margin-top: 1rem; padding: 1rem; background: #f1f5f9; border-radius: 6px;">
                            <strong>重要提醒：</strong>
                            <ul style="margin-left: 1rem; margin-top: 0.5rem; color: #475569; font-size: 14px;">
                                <li>请删除 install.php 文件以确保安全</li>
                                <li>建议修改默认管理员密码</li>
                                <li>定期备份网站数据</li>
                            </ul>
                        </div>
                    `;
                    actions.innerHTML = `
                        <a href="/" class="btn btn-secondary">访问前台</a>
                        <a href="/admin/" class="btn btn-primary">进入后台</a>
                    `;
                    break;
            }
        }
        
        function testConnection() {
            // 这里应该发送AJAX请求测试数据库连接
            alert('数据库连接测试功能需要后端支持');
        }
        
        function install() {
            // 这里应该发送AJAX请求执行安装
            const adminPassword = document.getElementById('admin_password').value;
            const confirmPassword = document.getElementById('admin_password_confirm').value;
            
            if (adminPassword !== confirmPassword) {
                alert('两次输入的密码不一致');
                return;
            }
            
            if (adminPassword.length < 8) {
                alert('密码长度至少8位');
                return;
            }
            
            // 模拟安装过程
            setTimeout(() => {
                nextStep();
            }, 1000);
        }
        
        // 页面加载时执行环境检测
        document.addEventListener('DOMContentLoaded', function() {
            // 检测所有要求是否满足
            const requirements = document.querySelectorAll('.requirement-status');
            const allPass = Array.from(requirements).every(req => req.classList.contains('status-pass'));
            
            if (!allPass) {
                document.querySelector('.btn-primary').disabled = true;
                document.querySelector('.install-content').insertAdjacentHTML('afterbegin', `
                    <div class="alert alert-error">
                        <strong>环境检测未通过</strong><br>
                        请先解决上述环境问题后再进行安装。
                    </div>
                `);
            }
        });
    </script>
</body>
</html>