<?php
// public/about.php - 关于页面
require_once '../includes/config.php';
require_once '../includes/Database.php';

$db = Database::getInstance();

// 获取网站统计数据
$stats = [
    'total_articles' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'],
    'total_views' => $db->fetchOne("SELECT SUM(views) as total FROM articles WHERE status = 'published'")['total'] ?? 0,
    'total_categories' => $db->fetchOne("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")['count'],
    'total_tags' => $db->fetchOne("SELECT COUNT(*) as count FROM tags")['count']
];

// 获取最近活跃的作者
$authors = $db->fetchAll("
    SELECT u.*, COUNT(a.id) as article_count, MAX(a.created_at) as last_article
    FROM users u
    LEFT JOIN articles a ON u.id = a.author_id AND a.status = 'published'
    WHERE u.role IN ('admin', 'editor', 'author')
    GROUP BY u.id
    HAVING article_count > 0
    ORDER BY last_article DESC
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>关于我们 - 梦幻博客</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --macaron-pink: #FFB6C1;
            --macaron-blue: #87CEEB;
            --macaron-green: #98FB98;
            --macaron-purple: #DDA0DD;
            --macaron-yellow: #FFFFE0;
            --macaron-orange: #FFE4B5;
            --macaron-lavender: #E6E6FA;
            --macaron-mint: #F0FFFF;
            --macaron-peach: #FFCCCB;
            --macaron-cream: #FFFACD;
            
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.37);
            --shadow-heavy: 0 12px 40px rgba(31, 38, 135, 0.5);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, var(--macaron-pink), var(--macaron-blue), var(--macaron-green), var(--macaron-purple));
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* 导航栏 */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--macaron-purple), var(--macaron-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-link {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            transform: translateY(-2px);
        }

        /* 主要内容 */
        .main-content {
            margin-top: 80px;
            padding: 2rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* 英雄区域 */
        .hero-about {
            text-align: center;
            padding: 6rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-about::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 30%, var(--macaron-yellow), transparent 50%),
                        radial-gradient(circle at 70% 70%, var(--macaron-mint), transparent 50%);
            opacity: 0.3;
            animation: heroFloat 8s ease-in-out infinite;
        }

        @keyframes heroFloat {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.05) rotate(2deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, var(--macaron-purple), var(--macaron-pink), var(--macaron-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: textShimmer 3s ease-in-out infinite;
        }

        @keyframes textShimmer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .hero-subtitle {
            font-size: 1.4rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        /* 统计区域 */
        .stats-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 3rem;
            margin: 4rem 0;
            box-shadow: var(--shadow-light);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.05);
        }

        .stat-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            background: linear-gradient(45deg, var(--macaron-purple), var(--macaron-pink));
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--macaron-purple), var(--macaron-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* 关于内容 */
        .about-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 3rem;
            margin: 4rem 0;
            box-shadow: var(--shadow-light);
        }

        .content-section {
            margin-bottom: 3rem;
        }

        .content-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--macaron-purple), var(--macaron-pink));
            border-radius: 2px;
        }

        .section-content {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .section-content p {
            margin-bottom: 1.5rem;
        }

        /* 团队成员 */
        .team-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 3rem;
            margin: 4rem 0;
            box-shadow: var(--shadow-light);
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .team-member {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--glass-border);
        }

        .team-member:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-light);
        }

        .member-avatar {
            width: 100px;
            height: 100px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--macaron-purple), var(--macaron-pink));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
        }

        .member-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .member-role {
            color: var(--macaron-purple);
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .member-stats {
            display: flex;
            justify-content: center;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* 时间线 */
        .timeline-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 3rem;
            margin: 4rem 0;
            box-shadow: var(--shadow-light);
        }

        .timeline {
            position: relative;
            padding: 2rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--macaron-purple), var(--macaron-pink));
            transform: translateX(-50%);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 3rem;
            width: 50%;
        }

        .timeline-item:nth-child(odd) {
            left: 0;
            padding-right: 3rem;
            text-align: right;
        }

        .timeline-item:nth-child(even) {
            left: 50%;
            padding-left: 3rem;
            text-align: left;
        }

        .timeline-dot {
            position: absolute;
            top: 0;
            width: 20px;
            height: 20px;
            background: var(--macaron-purple);
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--macaron-purple);
        }

        .timeline-item:nth-child(odd) .timeline-dot {
            right: -10px;
        }

        .timeline-item:nth-child(even) .timeline-dot {
            left: -10px;
        }

        .timeline-content {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid var(--glass-border);
        }

        .timeline-date {
            color: var(--macaron-purple);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .timeline-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .timeline-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* 联系方式 */
        .contact-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 3rem;
            margin: 4rem 0;
            box-shadow: var(--shadow-light);
            text-align: center;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .contact-item {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            transition: transform 0.3s ease;
        }

        .contact-item:hover {
            transform: translateY(-5px);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--macaron-purple), var(--macaron-pink));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .contact-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .contact-value {
            color: var(--text-secondary);
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .timeline::before {
                left: 20px;
            }
            
            .timeline-item {
                width: 100%;
                left: 0 !important;
                padding-left: 3rem !important;
                padding-right: 0 !important;
                text-align: left !important;
            }
            
            .timeline-dot {
                left: 10px !important;
                right: auto !important;
            }
            
            .nav-menu {
                display: none;
            }
        }

        /* 滚动条美化 */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--macaron-purple);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--macaron-pink);
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">✨ DreamBlog</a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">首页</a></li>
                <li><a href="articles.php" class="nav-link">文章</a></li>
                <li><a href="categories.php" class="nav-link">分类</a></li>
                <li><a href="about.php" class="nav-link">关于</a></li>
            </ul>
        </div>
    </nav>

    <!-- 主要内容 -->
    <main class="main-content">
        <div class="container">
            <!-- 英雄区域 -->
            <section class="hero-about">
                <div class="hero-content">
                    <h1 class="hero-title">关于梦幻博客</h1>
                    <p class="hero-subtitle">
                        我们致力于创造一个美好的写作和阅读平台，让每个人都能在这里分享自己的故事，
                        发现生活中的美好瞬间。用文字连接心灵，用故事温暖世界。
                    </p>
                </div>
            </section>

            <!-- 统计数据 -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['total_articles']); ?></div>
                        <div class="stat-label">篇文章</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['total_views']); ?></div>
                        <div class="stat-label">次阅读</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['total_categories']); ?></div>
                        <div class="stat-label">个分类</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats['total_tags']); ?></div>
                        <div class="stat-label">个标签</div>
                    </div>
                </div>
            </section>

            <!-- 关于内容 -->
            <section class="about-content">
                <div class="content-section">
                    <h2 class="section-title">我们的故事</h2>
                    <div class="section-content">
                        <p>
                            梦幻博客诞生于对美好生活的向往和对知识分享的热爱。我们相信，每个人都有自己独特的故事和见解，
                            值得被记录、被分享、被聆听。在这个快节奏的时代，我们希望为大家提供一个温馨的港湾，
                            让文字成为连接心灵的桥梁。
                        </p>
                        <p>
                            从技术分享到生活感悟，从旅行见闻到美食制作，从摄影艺术到音乐欣赏，
                            我们涵盖了生活的各个方面。每一篇文章都经过精心编辑，每一张图片都经过仔细挑选，
                            只为给读者带来最好的阅读体验。
                        </p>
                    </div>
                </div>

                <div class="content-section">
                    <h2 class="section-title">我们的使命</h2>
                    <div class="section-content">
                        <p>
                            <strong>传播知识：</strong>我们致力于分享有价值的知识和经验，帮助读者在各个领域获得成长。
                        </p>
                        <p>
                            <strong>记录美好：</strong>捕捉生活中的美好瞬间，用文字和图片记录下值得珍藏的回忆。
                        </p>
                        <p>
                            <strong>连接心灵：</strong>通过文字搭建起沟通的桥梁，让思想得以交流，让情感得以共鸣。
                        </p>
                        <p>
                            <strong>启发创造：</strong>激发读者的创作灵感，鼓励大家用自己的方式表达和创造。
                        </p>
                    </div>
                </div>
            </section>

            <!-- 团队成员 -->
            <?php if (!empty($authors)): ?>
            <section class="team-section">
                <h2 class="section-title">我们的团队</h2>
                <div class="team-grid">
                    <?php foreach ($authors as $author): ?>
                    <div class="team-member">
                        <div class="member-avatar">
                            <?php echo strtoupper(substr($author['username'], 0, 2)); ?>
                        </div>
                        <div class="member-name"><?php echo htmlspecialchars($author['real_name'] ?: $author['username']); ?></div>
                        <div class="member-role">
                            <?php
                            $roleLabels = [
                                'admin' => '管理员',
                                'editor' => '编辑',
                                'author' => '作者'
                            ];
                            echo $roleLabels[$author['role']] ?? '贡献者';
                            ?>
                        </div>
                        <div class="member-stats">
                            <span><i class="fas fa-file-alt"></i> <?php echo $author['article_count']; ?> 篇文章</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- 发展时间线 -->
            <section class="timeline-section">
                <h2 class="section-title">发展历程</h2>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-date">2024年3月</div>
                            <div class="timeline-title">梦幻博客正式上线</div>
                            <div class="timeline-desc">经过数月的精心设计和开发，梦幻博客正式与大家见面。</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-date">2024年4月</div>
                            <div class="timeline-title">音乐播放器功能上线</div>
                            <div class="timeline-desc">为了提升用户体验，我们添加了优雅的音乐播放器功能。</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-date">2024年5月</div>
                            <div class="timeline-title">移动端优化完成</div>
                            <div class="timeline-desc">完成了移动端的全面优化，确保在各种设备上都有完美的体验。</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-date">2024年6月</div>
                            <div class="timeline-title">搜索功能增强</div>
                            <div class="timeline-desc">升级了搜索算法，让用户能够更快速地找到想要的内容。</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 联系方式 -->
            <section class="contact-section">
                <h2 class="section-title">联系我们</h2>
                <p class="section-content">有任何问题或建议，欢迎随时联系我们</p>
                <div class="contact-grid">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-label">邮箱</div>
                        <div class="contact-value">hello@dreamblog.com</div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="contact-label">在线客服</div>
                        <div class="contact-value">工作日 9:00-18:00</div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fab fa-weixin"></i>
                        </div>
                        <div class="contact-label">微信公众号</div>
                        <div class="contact-value">DreamBlog梦幻博客</div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fab fa-weibo"></i>
                        </div>
                        <div class="contact-label">官方微博</div>
                        <div class="contact-value">@梦幻博客官方</div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        // 滚动效果
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // 统计数字动画
        function animateNumbers() {
            const stats = document.querySelectorAll('.stat-number');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = parseInt(entry.target.textContent.replace(/,/g, ''));
                        let current = 0;
                        const increment = target / 50;
                        
                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= target) {
                                current = target;
                                clearInterval(timer);
                            }
                            entry.target.textContent = Math.floor(current).toLocaleString();
                        }, 30);
                        
                        observer.unobserve(entry.target);
                    }
                });
            });

            stats.forEach(stat => observer.observe(stat));
        }

        // 时间线动画
        function animateTimeline() {
            const timelineItems = document.querySelectorAll('.timeline-item');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 200);
                    }
                });
            });

            timelineItems.forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(item);
            });
        }

        // 团队成员动画
        function animateTeam() {
            const members = document.querySelectorAll('.team-member');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0) scale(1)';
                        }, index * 150);
                    }
                });
            });

            members.forEach(member => {
                member.style.opacity = '0';
                member.style.transform = 'translateY(30px) scale(0.9)';
                member.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(member);
            });
        }

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            animateNumbers();
            animateTimeline();
            animateTeam();
        });

        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>


<?php
// public/tag.php - 标签页面
require_once '../includes/config.php';
require_once '../includes/Database.php';

$db = Database::getInstance();

$tagId = $_GET['id'] ?? '';
$tagSlug = $_GET['slug'] ?? '';

if (!$tagId && !$tagSlug) {
    header('HTTP/1.1 404 Not Found');
    include '404.php';
    exit;
}

// 获取标签信息
if ($tagId) {
    $tag = $db->fetchOne("SELECT * FROM tags WHERE id = ?", [$tagId]);
} else {
    $tag = $db->fetchOne("SELECT * FROM tags WHERE slug = ?", [$tagSlug]);
}

if (!$tag) {
    header('HTTP/1.1 404 Not Found');
    include '404.php';
    exit;
}

// 获取分页参数
$page = max(1, $_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

// 获取标签下的文章
$articles = $db->fetchAll("
    SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name
    FROM articles a
    JOIN article_tags at ON a.id = at.article_id
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE at.tag_id = ? AND a.status = 'published'
    ORDER BY a.created_at DESC 
    LIMIT ? OFFSET ?
", [$tag['id'], $limit, $offset]);

// 获取总数
$totalCount = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM articles a
    JOIN article_tags at ON a.id = at.article_id
    WHERE at.tag_id = ? AND a.status = 'published'
", [$tag['id']])['total'];

$totalPages = ceil($totalCount / $limit);

// 获取相关标签
$relatedTags = $db->fetchAll("
    SELECT t.*, COUNT(at2.article_id) as article_count
    FROM tags t
    JOIN article_tags at1 ON t.id = at1.tag_id
    JOIN article_tags at2 ON at1.article_id = at2.article_id
    WHERE at2.tag_id = ? AND t.id != ?
    GROUP BY t.id
    ORDER BY article_count DESC
    LIMIT 10
", [$tag['id'], $tag['id']]);

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return '刚刚';
    if ($time < 3600) return floor($time/60) . '分钟前';
    if ($time < 86400) return floor($time/3600) . '小时前';
    if ($time < 2592000) return floor($time/86400) . '天前';
    if ($time < 31536000) return floor($time/2592000) . '个月前';
    return floor($time/31536000) . '年前';
}

function getExcerpt($content, $length = 120) {
    $text = strip_tags($content);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>标签：<?php echo htmlspecialchars($tag['name']); ?> - 梦幻博客</title>
    <meta name="description" content="<?php echo htmlspecialchars($tag['description'] ?: "关于{$tag['name']}的文章"); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* 复用前面的基础样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --macaron-pink: #FFB6C1;
            --macaron-blue: #87CEEB;
            --macaron-green: #98FB98;
            --macaron-purple: #DDA0DD;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.37);
            --shadow-heavy: 0 12px 40px rgba(31, 38, 135, 0.5);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(-45deg, var(--macaron-pink), var(--macaron-blue), var(--macaron-green), var(--macaron-purple));
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* 标签头部 */
        .tag-header {
            background: linear-gradient(135deg, var(--macaron-purple), var(--macaron-pink));
            padding: 6rem 0 4rem;
            margin-top: 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .tag-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.1), transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(255,255,255,0.1), transparent 50%);
            animation: headerFloat 8s ease-in-out infinite;
        }

        @keyframes headerFloat {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.05) rotate(1deg); }
        }

        .tag-header-content {
            position: relative;
            z-index: 2;
        }

        .tag-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .tag-name {
            font-size: 3.5rem;
            color: white;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .tag-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .tag-stats {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }

        /* 相关标签 */
        .related-tags-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin: 3rem 0;
            box-shadow: var(--shadow-light);
        }

        .related-tags-title {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .related-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .related-tag {
            background: var(--macaron-purple);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .related-tag:hover {
            background: var(--macaron-pink);
            transform: translateY(-2px) scale(1.05);
        }

        .related-tag-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        /* 文章网格和其他样式复用前面的代码 */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .article-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
            position: relative;
        }

        .article-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--macaron-pink), var(--macaron-blue), var(--macaron-green));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .article-card:hover::before {
            opacity: 1;
        }

        .article-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-heavy);
        }

        /* 其他样式... */
        .navbar { /* 导航栏样式 */ }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; }
        /* 等等... */
    </style>
</head>
<body>
    <!-- 导航栏 (复用) -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">✨ DreamBlog</a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">首页</a></li>
                <li><a href="articles.php" class="nav-link">文章</a></li>
                <li><a href="categories.php" class="nav-link">分类</a></li>
                <li><a href="about.php" class="nav-link">关于</a></li>
            </ul>
        </div>
    </nav>

    <!-- 标签头部 -->
    <header class="tag-header">
        <div class="container">
            <div class="tag-header-content">
                <div class="tag-icon">
                    <i class="fas fa-tag"></i>
                </div>
                <h1 class="tag-name"><?php echo htmlspecialchars($tag['name']); ?></h1>
                <?php if ($tag['description']): ?>
                    <p class="tag-description"><?php echo htmlspecialchars($tag['description']); ?></p>
                <?php endif; ?>
                <div class="tag-stats">
                    <i class="fas fa-file-alt"></i>
                    共 <?php echo number_format($totalCount); ?> 篇文章
                </div>
            </div>
        </div>
    </header>

    <!-- 主要内容 -->
    <main class="main-content">
        <div class="container">
            <!-- 相关标签 -->
            <?php if (!empty($relatedTags)): ?>
                <section class="related-tags-section">
                    <h3 class="related-tags-title">
                        <i class="fas fa-tags"></i> 相关标签
                    </h3>
                    <div class="related-tags">
                        <?php foreach ($relatedTags as $relatedTag): ?>
                            <a href="tag.php?id=<?php echo $relatedTag['id']; ?>" class="related-tag">
                                <?php echo htmlspecialchars($relatedTag['name']); ?>
                                <span class="related-tag-count"><?php echo $relatedTag['article_count']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- 文章列表 -->
            <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <i class="fas fa-tag"></i>
                    <h3>暂无文章</h3>
                    <p>该标签下还没有文章，敬请期待。</p>
                </div>
            <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                        <article class="article-card">
                            <?php if ($article['featured_image']): ?>
                                <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                     class="article-image">
                            <?php else: ?>
                                <img src="https://picsum.photos/400/200?random=<?php echo $article['id']; ?>" 
                                     alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                     class="article-image">
                            <?php endif; ?>
                            
                            <div class="article-content">
                                <div class="article-meta">
                                    <?php if ($article['category_name']): ?>
                                        <a href="articles.php?category=<?php echo urlencode($article['category_slug']); ?>" 
                                           class="article-category">
                                            <?php echo htmlspecialchars($article['category_name']); ?>
                                        </a>
                                    <?php endif; ?>
                                    <span><i class="fas fa-calendar"></i> <?php echo getTimeAgo($article['created_at']); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author_name']); ?></span>
                                </div>
                                
                                <h3 class="article-title">
                                    <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h3>
                                
                                <p class="article-excerpt">
                                    <?php echo htmlspecialchars($article['excerpt'] ?: getExcerpt($article['content'])); ?>
                                </p>
                                
                                <div class="article-footer">
                                    <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>" class="read-more">
                                        阅读更多 <i class="fas fa-arrow-right"></i>
                                    </a>
                                    <div class="article-stats">
                                        <span><i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- 分页 (复用前面的分页代码) -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <!-- 分页按钮... -->
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // 复用前面的JavaScript代码
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // 文章卡片动画
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.article-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                    }
                });
            });

            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>


<?php
// public/js/music-player.js - 独立的音乐播放器JS文件
?>
class MusicPlayer {
    constructor() {
        this.currentSong = 0;
        this.isPlaying = false;
        this.isExpanded = false;
        this.volume = 50;
        this.currentTime = 0;
        this.duration = 0;
        
        this.playlist = [
            { 
                title: "梦幻旋律", 
                artist: "未知艺术家", 
                duration: 225, // 3:45 in seconds
                url: "audio/dream-melody.mp3" // 实际音频文件路径
            },
            { 
                title: "星空下的舞蹈", 
                artist: "月光乐团", 
                duration: 252, // 4:12 in seconds
                url: "audio/starry-dance.mp3"
            },
            { 
                title: "温柔的风", 
                artist: "自然之声", 
                duration: 208, // 3:28 in seconds
                url: "audio/gentle-wind.mp3"
            },
            { 
                title: "城市夜曲", 
                artist: "现代音乐", 
                duration: 275, // 4:35 in seconds
                url: "audio/city-night.mp3"
            }
        ];
        
        this.initializeElements();
        this.bindEvents();
        this.updateDisplay();
    }
    
    initializeElements() {
        this.player = document.getElementById('musicPlayer');
        this.playerToggle = document.getElementById('playerToggle');
        this.playBtn = document.getElementById('playBtn');
        this.prevBtn = document.getElementById('prevBtn');
        this.nextBtn = document.getElementById('nextBtn');
        this.progressBar = document.getElementById('progressBar');
        this.progressFill = document.getElementById('progressFill');
        this.songInfo = document.getElementById('songInfo');
        this.volumeSlider = document.getElementById('volumeSlider');
        
        // Create audio element
        this.audio = new Audio();
        this.audio.volume = this.volume / 100;
        this.audio.preload = 'metadata';
    }
    
    bindEvents() {
        this.playerToggle?.addEventListener('click', () => this.togglePlayer());
        this.playBtn?.addEventListener('click', () => this.togglePlay());
        this.prevBtn?.addEventListener('click', () => this.previousSong());
        this.nextBtn?.addEventListener('click', () => this.nextSong());
        this.progressBar?.addEventListener('click', (e) => this.seekTo(e));
        this.volumeSlider?.addEventListener('input', (e) => this.setVolume(e.target.value));
        
        // Audio events
        this.audio.addEventListener('loadedmetadata', () => {
            this.duration = this.audio.duration;
        });
        
        this.audio.addEventListener('timeupdate', () => {
            this.currentTime = this.audio.currentTime;
            this.updateProgress();
        });
        
        this.audio.addEventListener('ended', () => {
            this.nextSong();
        });
        
        this.audio.addEventListener('error', (e) => {
            console.warn('Audio error:', e);
            this.nextSong(); // Skip to next song on error
        });
    }
    
    togglePlayer() {
        this.isExpanded = !this.isExpanded;
        this.player?.classList.toggle('expanded', this.isExpanded);
        
        const icon = this.playerToggle?.querySelector('i');
        if (icon) {
            icon.className = this.isExpanded ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
        }
    }
    
    async togglePlay() {
        if (this.isPlaying) {
            this.pause();
        } else {
            await this.play();
        }
    }
    
    async play() {
        try {
            if (!this.audio.src) {
                this.loadCurrentSong();
            }
            
            await this.audio.play();
            this.isPlaying = true;
            this.updatePlayButton();
        } catch (error) {
            console.warn('Play failed:', error);
            // Fallback to simulation mode
            this.simulatePlay();
        }
    }
    
    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.updatePlayButton();
        this.stopSimulation();
    }
    
    previousSong() {
        this.currentSong = (this.currentSong - 1 + this.playlist.length) % this.playlist.length;
        this.loadCurrentSong();
        if (this.isPlaying) {
            this.play();
        }
    }
    
    nextSong() {
        this.currentSong = (this.currentSong + 1) % this.playlist.length;
        this.loadCurrentSong();
        if (this.isPlaying) {
            this.play();
        }
    }
    
    loadCurrentSong() {
        const song = this.playlist[this.currentSong];
        this.audio.src = song.url;
        this.duration = song.duration;
        this.currentTime = 0;
        this.updateDisplay();
        this.updateProgress();
    }
    
    seekTo(event) {
        if (!this.progressBar) return;
        
        const rect = this.progressBar.getBoundingClientRect();
        const percent = (event.clientX - rect.left) / rect.width;
        const newTime = percent * this.duration;
        
        if (this.audio.src) {
            this.audio.currentTime = newTime;
        } else {
            this.currentTime = newTime;
            this.updateProgress();
        }
    }
    
    setVolume(value) {
        this.volume = value;
        this.audio.volume = value / 100;
    }
    
    updateDisplay() {
        if (!this.songInfo) return;
        
        const song = this.playlist[this.currentSong];
        this.songInfo.textContent = `${song.title} - ${song.artist}`;
    }
    
    updatePlayButton() {
        const icon = this.playBtn?.querySelector('i');
        if (icon) {
            icon.className = this.isPlaying ? 'fas fa-pause' : 'fas fa-play';
        }
    }
    
    updateProgress() {
        if (!this.progressFill || !this.duration) return;
        
        const percent = (this.currentTime / this.duration) * 100;
        this.progressFill.style.width = `${Math.min(percent, 100)}%`;
    }
    
    // Fallback simulation for when audio files are not available
    simulatePlay() {
        this.isPlaying = true;
        this.updatePlayButton();
        
        this.simulationInterval = setInterval(() => {
            if (!this.isPlaying) return;
            
            this.currentTime += 0.1;
            if (this.currentTime >= this.duration) {
                this.currentTime = 0;
                this.nextSong();
                return;
            }
            
            this.updateProgress();
        }, 100);
    }
    
    stopSimulation() {
        if (this.simulationInterval) {
            clearInterval(this.simulationInterval);
            this.simulationInterval = null;
        }
    }
    
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
}

// Initialize music player when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.musicPlayer = new MusicPlayer();
});


<?php
// public/css/style.css - 独立的CSS文件内容
// 这里包含所有的样式，可以独立引用
?>

/* CSS文件内容 */
:root {
    --macaron-pink: #FFB6C1;
    --macaron-blue: #87CEEB;
    --macaron-green: #98FB98;
    --macaron-purple: #DDA0DD;
    --macaron-yellow: #FFFFE0;
    --macaron-orange: #FFE4B5;
    --macaron-lavender: #E6E6FA;
    --macaron-mint: #F0FFFF;
    --macaron-peach: #FFCCCB;
    --macaron-cream: #FFFACD;
    
    --glass-bg: rgba(255, 255, 255, 0.1);
    --glass-border: rgba(255, 255, 255, 0.2);
    --text-primary: #2D3748;
    --text-secondary: #4A5568;
    --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.37);
    --shadow-heavy: 0 12px 40px rgba(31, 38, 135, 0.5);
}

/* 所有之前定义的样式... */


<?php
// includes/config.php - 配置文件示例
?>
<?php
// includes/config.php - 数据库配置

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'cms_website');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

// 网站配置
define('SITE_URL', 'http://localhost/your-site');
define('SITE_NAME', '梦幻博客');
define('SITE_DESCRIPTION', '发现美好世界的写作平台');

// 文件上传配置
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// 缓存配置
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_TIME', 3600); // 1小时

// 邮件配置
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@yoursite.com');
define('MAIL_FROM_NAME', '梦幻博客');

// 分页配置
define('POSTS_PER_PAGE', 12);
define('ADMIN_POSTS_PER_PAGE', 20);

// 安全配置
define('HASH_ALGO', 'sha256');
define('SESSION_LIFETIME', 7200); // 2小时

// 调试模式
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('LOG_FILE', __DIR__ . '/../logs/error.log');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 会话设置
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params(SESSION_LIFETIME);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}