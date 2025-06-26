<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>现代化CMS网站 - 企业级内容管理平台</title>
    <meta name="description" content="现代化CMS内容管理系统，提供企业级功能和安全性，适合各种规模的网站使用。">
    <meta name="keywords" content="CMS,内容管理,网站建设,企业官网">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #8b5cf6;
            --secondary-color: #f59e0b;
            --accent-color: #10b981;
            --dark-color: #1f2937;
            --darker-color: #111827;
            --light-color: #f8fafc;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --white: #ffffff;
            --gradient-primary: linear-gradient(135deg, #6366f1, #8b5cf6);
            --gradient-secondary: linear-gradient(135deg, #f59e0b, #f97316);
            --gradient-dark: linear-gradient(135deg, #1f2937, #111827);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            background: var(--white);
            overflow-x: hidden;
        }

        /* 导航栏 */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            z-index: 1000;
            transition: var(--transition);
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-lg);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            position: relative;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }

        .nav-link.active {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-btn, .theme-toggle {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--gray-100);
            border-radius: 8px;
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-btn:hover, .theme-toggle:hover {
            background: var(--gray-200);
            color: var(--primary-color);
        }

        .mobile-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border: none;
            background: none;
            color: var(--gray-700);
            cursor: pointer;
            font-size: 1.2rem;
        }

        /* 英雄区域 */
        .hero {
            padding: 8rem 2rem 6rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-content {
            color: white;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff, #e2e8f0);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: white;
            color: var(--primary-color);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .hero-visual {
            position: relative;
        }

        .hero-image {
            width: 100%;
            max-width: 500px;
            height: auto;
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            transform: perspective(1000px) rotateY(-15deg) rotateX(5deg);
            transition: var(--transition);
        }

        .hero-image:hover {
            transform: perspective(1000px) rotateY(-10deg) rotateX(2deg) scale(1.05);
        }

        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 20%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 30%;
            left: 70%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* 特性区域 */
        .features {
            padding: 6rem 2rem;
            background: var(--light-color);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-badge {
            display: inline-block;
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }

        .section-description {
            font-size: 1.1rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--gray-600);
            line-height: 1.6;
        }

        /* 统计区域 */
        .stats {
            padding: 4rem 2rem;
            background: var(--gradient-dark);
            color: white;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            padding: 1rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffffff, #e2e8f0);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        /* 最新文章区域 */
        .articles {
            padding: 6rem 2rem;
            background: white;
        }

        .articles-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .article-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .article-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: var(--transition);
        }

        .article-card:hover .article-image {
            transform: scale(1.05);
        }

        .article-content {
            padding: 1.5rem;
        }

        .article-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        .article-category {
            background: var(--gradient-primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .article-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .article-title a {
            color: inherit;
            text-decoration: none;
            transition: var(--transition);
        }

        .article-title a:hover {
            color: var(--primary-color);
        }

        .article-excerpt {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .article-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .read-more {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .read-more:hover {
            transform: translateX(5px);
        }

        .article-views {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        /* 页脚 */
        .footer {
            background: var(--gray-900);
            color: white;
            padding: 3rem 2rem 1rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: white;
        }

        .footer-section p, .footer-section li {
            color: var(--gray-300);
            line-height: 1.6;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section a {
            color: var(--gray-300);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-section a:hover {
            color: var(--primary-color);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: var(--gray-800);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-300);
            text-decoration: none;
            transition: var(--transition);
        }

        .social-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid var(--gray-800);
            padding-top: 1rem;
            text-align: center;
            color: var(--gray-400);
        }

        /* 加载动画 */
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--gray-200);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .nav-menu {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 70px);
                background: white;
                flex-direction: column;
                justify-content: flex-start;
                padding: 2rem;
                transition: var(--transition);
                box-shadow: var(--shadow-lg);
            }

            .nav-menu.active {
                left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .hero {
                padding: 6rem 2rem 4rem;
                min-height: auto;
            }

            .hero-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .features-grid, .articles-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .nav-container {
                padding: 1rem;
            }

            .hero {
                padding: 5rem 1rem 3rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .features, .articles {
                padding: 4rem 1rem;
            }

            .btn {
                padding: 0.75rem 1.5rem;
                font-size: 0.9rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <span>ModernCMS</span>
            </a>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="#" class="nav-link active">首页</a></li>
                <li><a href="#" class="nav-link">文章</a></li>
                <li><a href="#" class="nav-link">分类</a></li>
                <li><a href="#" class="nav-link">关于</a></li>
                <li><a href="#" class="nav-link">联系</a></li>
            </ul>
            
            <div class="nav-actions">
                <button class="search-btn" id="searchBtn">
                    <i class="fas fa-search"></i>
                </button>
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <section class="hero">
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>
        
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-star"></i>
                    <span>现代化企业级CMS系统</span>
                </div>
                
                <h1 class="hero-title">
                    强大的内容管理<br>
                    触手可及
                </h1>
                
                <p class="hero-description">
                    基于PHP+MySQL开发的现代化内容管理系统，具备企业级功能和安全性，
                    响应式设计，SEO优化，适合各种规模的网站使用。
                </p>
                
                <div class="hero-buttons">
                    <a href="#" class="btn btn-primary">
                        <i class="fas fa-rocket"></i>
                        立即开始
                    </a>
                    <a href="#" class="btn btn-secondary">
                        <i class="fas fa-play"></i>
                        观看演示
                    </a>
                </div>
            </div>
            
            <div class="hero-visual">
                <img src="data:image/svg+xml,%3Csvg width='500' height='400' viewBox='0 0 500 400' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='500' height='400' rx='20' fill='url(%23gradient)'/%3E%3Crect x='40' y='40' width='420' height='60' rx='10' fill='white' fill-opacity='0.1'/%3E%3Crect x='60' y='55' width='100' height='30' rx='5' fill='white' fill-opacity='0.8'/%3E%3Crect x='40' y='120' width='200' height='240' rx='10' fill='white' fill-opacity='0.1'/%3E%3Crect x='260' y='120' width='200' height='115' rx='10' fill='white' fill-opacity='0.1'/%3E%3Crect x='260' y='245' width='200' height='115' rx='10' fill='white' fill-opacity='0.1'/%3E%3Cdefs%3E%3ClinearGradient id='gradient' x1='0' y1='0' x2='500' y2='400' gradientUnits='userSpaceOnUse'%3E%3Cstop stop-color='%236366f1'/%3E%3Cstop offset='1' stop-color='%238b5cf6'/%3E%3C/linearGradient%3E%3C/defs%3E%3C/svg%3E" alt="CMS Dashboard Preview" class="hero-image">
            </div>
        </div>
    </section>

    <!-- 特性区域 -->
    <section class="features">
        <div class="features-container">
            <div class="section-header">
                <div class="section-badge">核心特性</div>
                <h2 class="section-title">为什么选择我们的CMS？</h2>
                <p class="section-description">
                    我们提供完整的解决方案，从内容创建到网站管理，
                    让您专注于内容创作，而不是技术细节。
                </p>
            </div>
            
            <div class="features-grid" id="featuresGrid">
                <!-- 特性卡片将通过JavaScript动态加载 -->
            </div>
        </div>
    </section>

    <!-- 统计区域 -->
    <section class="stats">
        <div class="stats-container" id="statsContainer">
            <div class="stat-item">
                <div class="stat-number" data-target="10000">0</div>
                <div class="stat-label">活跃用户</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-target="50000">0</div>
                <div class="stat-label">发布文章</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-target="99.9">0</div>
                <div class="stat-label">系统稳定性</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-target="24">0</div>
                <div class="stat-label">技术支持</div>
            </div>
        </div>
    </section>

    <!-- 最新文章区域 -->
    <section class="articles">
        <div class="articles-container">
            <div class="section-header">
                <div class="section-badge">最新内容</div>
                <h2 class="section-title">精选文章</h2>
                <p class="section-description">
                    浏览我们的最新文章，了解行业趋势和技术分享，
                    获取有价值的内容和见解。
                </p>
            </div>
            
            <div class="articles-grid" id="articlesGrid">
                <!-- 文章卡片将通过JavaScript动态加载 -->
            </div>
            
            <div class="loading" id="articlesLoading">
                <div class="spinner"></div>
                <p>加载中...</p>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="#" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i>
                    查看更多文章
                </a>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>ModernCMS</h3>
                    <p>现代化的内容管理系统，为您的网站提供强大的功能和优秀的用户体验。</p>
                    <div class="social-links">
                        <a href="#" class="social-link">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="fab fa-weixin"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>产品</h3>
                    <ul>
                        <li><a href="#">CMS系统</a></li>
                        <li><a href="#">模板库</a></li>
                        <li><a href="#">插件市场</a></li>
                        <li><a href="#">API文档</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>支持</h3>
                    <ul>
                        <li><a href="#">帮助中心</a></li>
                        <li><a href="#">开发文档</a></li>
                        <li><a href="#">社区论坛</a></li>
                        <li><a href="#">联系我们</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>公司</h3>
                    <ul>
                        <li><a href="#">关于我们</a></li>
                        <li><a href="#">加入我们</a></li>
                        <li><a href="#">隐私政策</a></li>
                        <li><a href="#">服务条款</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 ModernCMS. 保留所有权利.</p>
            </div>
        </div>
    </footer>

    <script>
        // 全局变量和配置
        const API_BASE_URL = '/api';
        let isLoading = false;

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            initializeNavigation();
            initializeAnimations();
            loadFeatures();
            loadLatestArticles();
            initializeStats();
            initializeTheme();
            initializeSearch();
        });

        // 导航栏功能
        function initializeNavigation() {
            const navbar = document.getElementById('navbar');
            const mobileToggle = document.getElementById('mobileToggle');
            const navMenu = document.getElementById('navMenu');

            // 滚动效果
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // 移动端菜单切换
            mobileToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            });

            // 点击菜单项关闭移动端菜单
            navMenu.addEventListener('click', function(e) {
                if (e.target.classList.contains('nav-link')) {
                    navMenu.classList.remove('active');
                    mobileToggle.querySelector('i').classList.add('fa-bars');
                    mobileToggle.querySelector('i').classList.remove('fa-times');
                }
            });
        }

        // 初始化动画
        function initializeAnimations() {
            // Intersection Observer for animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            // 为所有需要动画的元素添加观察
            document.querySelectorAll('.feature-card, .article-card, .stat-item').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        }

        // 加载特性数据
        async function loadFeatures() {
            const featuresData = [
                {
                    icon: 'fas fa-palette',
                    title: '响应式设计',
                    description: '完美适配PC、平板、手机等各种设备，为用户提供一致的优秀体验。'
                },
                {
                    icon: 'fas fa-shield-alt',
                    title: '安全防护',
                    description: 'SQL注入防护、XSS攻击防护、CSRF防护等多重安全机制保护您的网站。'
                },
                {
                    icon: 'fas fa-rocket',
                    title: '性能优化',
                    description: '多级缓存、数据库优化、图片懒加载等技术确保网站快速加载。'
                },
                {
                    icon: 'fas fa-search',
                    title: 'SEO优化',
                    description: '内置SEO工具，智能生成sitemap，搜索引擎友好的URL结构。'
                },
                {
                    icon: 'fas fa-users',
                    title: '用户管理',
                    description: '5级用户权限系统，精细化权限控制，满足不同规模团队需求。'
                },
                {
                    icon: 'fas fa-cogs',
                    title: '易于扩展',
                    description: '模块化架构设计，支持插件扩展，可根据需求自定义功能。'
                }
            ];

            const featuresGrid = document.getElementById('featuresGrid');
            featuresGrid.innerHTML = featuresData.map(feature => `
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="${feature.icon}"></i>
                    </div>
                    <h3 class="feature-title">${feature.title}</h3>
                    <p class="feature-description">${feature.description}</p>
                </div>
            `).join('');
        }

        // 加载最新文章
        async function loadLatestArticles() {
            const articlesGrid = document.getElementById('articlesGrid');
            const loading = document.getElementById('articlesLoading');
            
            loading.style.display = 'block';

            try {
                // 模拟API调用
                const articles = await simulateApiCall('/api/articles/latest', 1500);
                
                articlesGrid.innerHTML = articles.map(article => `
                    <article class="article-card">
                        <img src="${article.image}" alt="${article.title}" class="article-image">
                        <div class="article-content">
                            <div class="article-meta">
                                <span class="article-category">${article.category}</span>
                                <span><i class="fas fa-calendar"></i> ${article.date}</span>
                                <span><i class="fas fa-user"></i> ${article.author}</span>
                            </div>
                            <h3 class="article-title">
                                <a href="${article.url}">${article.title}</a>
                            </h3>
                            <p class="article-excerpt">${article.excerpt}</p>
                            <div class="article-footer">
                                <a href="${article.url}" class="read-more">
                                    阅读更多
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                                <div class="article-views">
                                    <i class="fas fa-eye"></i>
                                    <span>${article.views}</span>
                                </div>
                            </div>
                        </div>
                    </article>
                `).join('');

                loading.style.display = 'none';
            } catch (error) {
                loading.innerHTML = '<p style="color: var(--gray-500);">文章加载失败，请稍后重试</p>';
                console.error('Failed to load articles:', error);
            }
        }

        // 模拟API调用
        function simulateApiCall(url, delay = 1000) {
            return new Promise((resolve) => {
                setTimeout(() => {
                    // 模拟文章数据
                    const mockArticles = [
                        {
                            id: 1,
                            title: '如何构建现代化的CMS系统',
                            excerpt: '探讨现代CMS系统的架构设计原则，包括前后端分离、微服务架构、缓存策略等关键技术要点...',
                            image: 'data:image/svg+xml,%3Csvg width="350" height="200" viewBox="0 0 350 200" fill="none" xmlns="http://www.w3.org/2000/svg"%3E%3Crect width="350" height="200" fill="%236366f1"/%3E%3Crect x="20" y="20" width="80" height="40" rx="5" fill="white" fill-opacity="0.2"/%3E%3Crect x="20" y="80" width="200" height="8" rx="4" fill="white" fill-opacity="0.3"/%3E%3Crect x="20" y="100" width="150" height="8" rx="4" fill="white" fill-opacity="0.3"/%3E%3Crect x="20" y="120" width="180" height="8" rx="4" fill="white" fill-opacity="0.3"/%3E%3C/svg%3E',
                            category: '技术分享',
                            date: '2024-01-15',
                            author: '张三',
                            views: '1,234',
                            url: '/article/modern-cms-development'
                        },
                        {
                            id: 2,
                            title: 'PHP8.0新特性详解与最佳实践',
                            excerpt: '深入解析PHP8.0的新特性，包括联合类型、命名参数、属性等，以及在实际项目中的应用实践...',
                            image: 'data:image/svg+xml,%3Csvg width="350" height="200" viewBox="0 0 350 200" fill="none" xmlns="http://www.w3.org/2000/svg"%3E%3Crect width="350" height="200" fill="%238b5cf6"/%3E%3Ccircle cx="175" cy="100" r="40" fill="white" fill-opacity="0.2"/%3E%3Crect x="20" y="160" width="100" height="6" rx="3" fill="white" fill-opacity="0.3"/%3E%3Crect x="130" y="160" width="80" height="6" rx="3" fill="white" fill-opacity="0.3"/%3E%3C/svg%3E',
                            category: 'PHP开发',
                            date: '2024-01-12',
                            author: '李四',
                            views: '2,156',
                            url: '/article/php8-new-features'
                        },
                        {
                            id: 3,
                            title: '网站SEO优化完全指南',
                            excerpt: '从技术SEO到内容SEO，全面介绍网站搜索引擎优化的策略和技巧，提升网站在搜索结果中的排名...',
                            image: 'data:image/svg+xml,%3Csvg width="350" height="200" viewBox="0 0 350 200" fill="none" xmlns="http://www.w3.org/2000/svg"%3E%3Crect width="350" height="200" fill="%2310b981"/%3E%3Cpath d="M100 80L120 60L180 120L250 50" stroke="white" stroke-width="4" stroke-opacity="0.3" fill="none"/%3E%3Ccircle cx="180" cy="120" r="20" fill="white" fill-opacity="0.2"/%3E%3C/svg%3E',
                            category: 'SEO优化',
                            date: '2024-01-10',
                            author: '王五',
                            views: '3,421',
                            url: '/article/seo-optimization-guide'
                        }
                    ];
                    resolve(mockArticles);
                }, delay);
            });
        }

        // 初始化统计数字动画
        function initializeStats() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateNumber(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            });

            document.querySelectorAll('.stat-number').forEach(el => {
                observer.observe(el);
            });
        }

        // 数字动画
        function animateNumber(element) {
            const target = parseFloat(element.dataset.target);
            const duration = 2000;
            const startTime = performance.now();
            const isDecimal = target % 1 !== 0;

            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const current = target * easeOutQuart(progress);
                
                if (isDecimal) {
                    element.textContent = current.toFixed(1) + '%';
                } else if (target >= 1000) {
                    element.textContent = Math.floor(current).toLocaleString() + '+';
                } else {
                    element.textContent = Math.floor(current) + '/7';
                }

                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                }
            }

            requestAnimationFrame(updateNumber);
        }

        // 缓动函数
        function easeOutQuart(t) {
            return 1 - Math.pow(1 - t, 4);
        }

        // 主题切换
        function initializeTheme() {
            const themeToggle = document.getElementById('themeToggle');
            const currentTheme = localStorage.getItem('theme') || 'light';
            
            document.documentElement.setAttribute('data-theme', currentTheme);
            updateThemeIcon(currentTheme);

            themeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
        }

        // 更新主题图标
        function updateThemeIcon(theme) {
            const icon = document.querySelector('#themeToggle i');
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // 搜索功能
        function initializeSearch() {
            const searchBtn = document.getElementById('searchBtn');
            
            searchBtn.addEventListener('click', function() {
                // 可以在这里实现搜索模态框或跳转到搜索页面
                const searchTerm = prompt('请输入搜索关键词：');
                if (searchTerm) {
                    window.location.href = `/search?q=${encodeURIComponent(searchTerm)}`;
                }
            });
        }

        // 平滑滚动到指定元素
        function scrollToElement(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // 显示提示消息
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            
            Object.assign(toast.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '1rem 1.5rem',
                borderRadius: '8px',
                color: 'white',
                fontWeight: '600',
                zIndex: '9999',
                transform: 'translateX(100%)',
                transition: 'transform 0.3s ease'
            });

            if (type === 'success') toast.style.background = 'var(--accent-color)';
            else if (type === 'error') toast.style.background = '#ef4444';
            else toast.style.background = 'var(--primary-color)';

            document.body.appendChild(toast);

            setTimeout(() => toast.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }

        // 格式化数字
        function formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }

        // 防抖函数
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // 节流函数
        function throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            }
        }

        // 页面性能监控
        window.addEventListener('load', function() {
            if ('performance' in window) {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                console.log(`Page load time: ${loadTime}ms`);
                
                // 如果加载时间过长，可以显示提示
                if (loadTime > 3000) {
                    console.warn('Page load time is longer than expected');
                }
            }
        });

        // 错误处理
        window.addEventListener('error', function(e) {
            console.error('JavaScript error:', e.error);
            // 可以在这里添加错误上报逻辑
        });

        // 暗色主题CSS
        const darkThemeStyles = `
            [data-theme="dark"] {
                --white: #1f2937;
                --light-color: #111827;
                --gray-100: #374151;
                --gray-200: #4b5563;
                --gray-300: #6b7280;
                --gray-800: #f9fafb;
                --gray-900: #ffffff;
            }
            
            [data-theme="dark"] .navbar {
                background: rgba(31, 41, 55, 0.95);
                border-bottom-color: rgba(75, 85, 99, 0.5);
            }
            
            [data-theme="dark"] .feature-card,
            [data-theme="dark"] .article-card {
                background: var(--gray-800);
                border: 1px solid var(--gray-700);
            }
        `;

        // 动态添加暗色主题样式
        const styleSheet = document.createElement('style');
        styleSheet.textContent = darkThemeStyles;
        document.head.appendChild(styleSheet);
    </script>
</body>
</html>