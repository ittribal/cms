<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';

$auth = new Auth();

// 检查登录和权限
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// SEO分析类
class SEOAnalyzer 
{
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // 分析文章SEO
    public function analyzeArticles() {
        $articles = $this->db->fetchAll("
            SELECT id, title, slug, content, excerpt, meta_title, meta_description, views, status 
            FROM articles 
            WHERE status = 'published'
        ");
        
        $analysis = [];
        foreach ($articles as $article) {
            $analysis[] = [
                'id' => $article['id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'views' => $article['views'],
                'issues' => $this->findSEOIssues($article)
            ];
        }
        
        return $analysis;
    }
    
    // 查找SEO问题
    private function findSEOIssues($article) {
        $issues = [];
        
        // 标题长度检查
        if (strlen($article['title']) < 10) {
            $issues[] = ['type' => 'warning', 'message' => '标题过短（建议10-60字符）'];
        } elseif (strlen($article['title']) > 60) {
            $issues[] = ['type' => 'warning', 'message' => '标题过长（建议10-60字符）'];
        }
        
        // Meta标题检查
        if (empty($article['meta_title'])) {
            $issues[] = ['type' => 'error', 'message' => '缺少SEO标题'];
        } elseif (strlen($article['meta_title']) > 60) {
            $issues[] = ['type' => 'warning', 'message' => 'SEO标题过长'];
        }
        
        // Meta描述检查
        if (empty($article['meta_description'])) {
            $issues[] = ['type' => 'error', 'message' => '缺少SEO描述'];
        } elseif (strlen($article['meta_description']) < 120) {
            $issues[] = ['type' => 'warning', 'message' => 'SEO描述过短（建议120-160字符）'];
        } elseif (strlen($article['meta_description']) > 160) {
            $issues[] = ['type' => 'warning', 'message' => 'SEO描述过长（建议120-160字符）'];
        }
        
        // URL友好性检查
        if (strlen($article['slug']) < 3) {
            $issues[] = ['type' => 'warning', 'message' => 'URL别名过短'];
        }
        
        // 内容长度检查
        $contentLength = strlen(strip_tags($article['content']));
        if ($contentLength < 300) {
            $issues[] = ['type' => 'warning', 'message' => '内容过短（建议至少300字符）'];
        }
        
        // 摘要检查
        if (empty($article['excerpt'])) {
            $issues[] = ['type' => 'info', 'message' => '建议添加文章摘要'];
        }
        
        return $issues;
    }
    
    // 关键词密度分析
    public function analyzeKeywordDensity($content, $keyword) {
        $content = strtolower(strip_tags($content));
        $keyword = strtolower($keyword);
        
        $totalWords = str_word_count($content);
        $keywordCount = substr_count($content, $keyword);
        
        $density = $totalWords > 0 ? ($keywordCount / $totalWords) * 100 : 0;
        
        return [
            'keyword' => $keyword,
            'count' => $keywordCount,
            'total_words' => $totalWords,
            'density' => round($density, 2)
        ];
    }
    
    // 生成网站地图数据
    public function generateSitemapData() {
        $urls = [];
        
        // 添加主页
        $urls[] = [
            'url' => SITE_URL . '/public/',
            'lastmod' => date('c'),
            'changefreq' => 'daily',
            'priority' => '1.0'
        ];
        
        // 添加文章页面
        $articles = $this->db->fetchAll("
            SELECT slug, updated_at 
            FROM articles 
            WHERE status = 'published' 
            ORDER BY updated_at DESC
        ");
        
        foreach ($articles as $article) {
            $urls[] = [
                'url' => SITE_URL . '/public/article.php?slug=' . $article['slug'],
                'lastmod' => date('c', strtotime($article['updated_at'])),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        }
        
        // 添加分类页面
        $categories = $this->db->fetchAll("
            SELECT slug 
            FROM categories 
            WHERE status = 'active'
        ");
        
        foreach ($categories as $category) {
            $urls[] = [
                'url' => SITE_URL . '/public/category.php?slug=' . $category['slug'],
                'lastmod' => date('c'),
                'changefreq' => 'weekly',
                'priority' => '0.6'
            ];
        }
        
        return $urls;
    }
}

$seoAnalyzer = new SEOAnalyzer($db);
$message = '';
$error = '';

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_sitemap') {
        try {
            $sitemapData = $seoAnalyzer->generateSitemapData();
            $sitemapXml = generateSitemapXML($sitemapData);
            
            file_put_contents('../public/sitemap.xml', $sitemapXml);
            $auth->logAction('生成网站地图');
            $message = '网站地图生成成功';
        } catch (Exception $e) {
            $error = '网站地图生成失败：' . $e->getMessage();
        }
    } elseif ($action === 'generate_robots') {
        try {
            $robotsContent = generateRobotsTxt();
            file_put_contents('../public/robots.txt', $robotsContent);
            $auth->logAction('生成robots.txt');
            $message = 'robots.txt生成成功';
        } catch (Exception $e) {
            $error = 'robots.txt生成失败：' . $e->getMessage();
        }
    }
}

// 获取SEO分析结果
$seoAnalysis = $seoAnalyzer->analyzeArticles();

// 生成网站地图XML
function generateSitemapXML($urls) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($urls as $url) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($url['url']) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
        $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
        $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
        $xml .= '  </url>' . "\n";
    }
    
    $xml .= '</urlset>';
    return $xml;
}

// 生成robots.txt
function generateRobotsTxt() {
    global $db;
    
    $robotsContent = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'robots_txt'")['setting_value'] ?? '';
    
    if (empty($robotsContent)) {
        $robotsContent = "User-agent: *\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /includes/\n";
        $robotsContent .= "Disallow: /logs/\n";
        $robotsContent .= "Disallow: /backups/\n\n";
        $robotsContent .= "Sitemap: " . SITE_URL . "/public/sitemap.xml\n";
    }
    
    return $robotsContent;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO优化 - CMS后台</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include '../templates/admin_header.php'; ?>
    
    <div class="admin-container">
        <main class="main-content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>SEO优化工具</h1>
            </div>
            
            <!-- SEO工具 -->
            <div class="seo-tools">
                <h3>SEO工具</h3>
                <div class="tools-grid">
                    <div class="tool-card">
                        <h4>🗺️ 网站地图</h4>
                        <p>生成XML网站地图，帮助搜索引擎索引您的网站</p>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="generate_sitemap">
                            <button type="submit" class="btn btn-primary">生成网站地图</button>
                        </form>
                        <?php if (file_exists('../public/sitemap.xml')): ?>
                            <a href="../public/sitemap.xml" target="_blank" class="btn btn-sm btn-info">查看地图</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tool-card">
                        <h4>🤖 Robots.txt</h4>
                        <p>生成robots.txt文件，控制搜索引擎爬虫行为</p>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="generate_robots">
                            <button type="submit" class="btn btn-success">生成Robots.txt</button>
                        </form>
                        <?php if (file_exists('../public/robots.txt')): ?>
                            <a href="../public/robots.txt" target="_blank" class="btn btn-sm btn-info">查看文件</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tool-card">
                        <h4>📊 关键词分析</h4>
                        <p>分析文章关键词密度和SEO表现</p>
                        <button onclick="showKeywordAnalyzer()" class="btn btn-warning">关键词分析</button>
                    </div>
                    
                    <div class="tool-card">
                        <h4>🔗 内链分析</h4>
                        <p>分析网站内部链接结构和优化建议</p>
                        <button onclick="analyzeLinkStructure()" class="btn btn-info">分析链接</button>
                    </div>
                </div>
            </div>
            
            <!-- SEO分析结果 -->
            <div class="seo-analysis">
                <h3>文章SEO分析</h3>
                <div class="analysis-table">
                    <table>
                        <thead>
                            <tr>
                                <th>文章标题</th>
                                <th>URL</th>
                                <th>浏览量</th>
                                <th>SEO问题</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seoAnalysis as $analysis): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($analysis['title']); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($analysis['slug']); ?></code>
                                </td>
                                <td><?php echo number_format($analysis['views']); ?></td>
                                <td>
                                    <?php if (empty($analysis['issues'])): ?>
                                        <span class="status-good">✅ 良好</span>
                                    <?php else: ?>
                                        <div class="seo-issues">
                                            <?php foreach ($analysis['issues'] as $issue): ?>
                                                <div class="issue issue-<?php echo $issue['type']; ?>">
                                                    <?php echo htmlspecialchars($issue['message']); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="articles.php?action=edit&id=<?php echo $analysis['id']; ?>" 
                                       class="btn btn-sm btn-primary">编辑</a>
                                    <a href="../public/article.php?slug=<?php echo $analysis['slug']; ?>" 
                                       target="_blank" class="btn btn-sm btn-info">查看</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- SEO建议 -->
            <div class="seo-recommendations">
                <h3>SEO优化建议</h3>
                <div class="recommendations">
                    <div class="recommendation">
                        <h4>📝 内容优化</h4>
                        <ul>
                            <li>确保每篇文章都有独特的标题和描述</li>
                            <li>内容长度建议在300字以上</li>
                            <li>使用结构化的标题层次（H1, H2, H3）</li>
                            <li>添加相关的内链和外链</li>
                        </ul>
                    </div>
                    
                    <div class="recommendation">
                        <h4>🔍 关键词策略</h4>
                        <ul>
                            <li>关键词密度保持在1-3%之间</li>
                            <li>在标题、描述、内容中自然使用关键词</li>
                            <li>使用长尾关键词提高排名机会</li>
                            <li>避免关键词堆砌</li>
                        </ul>
                    </div>
                    
                    <div class="recommendation">
                        <h4>🔗 技术SEO</h4>
                        <ul>
                            <li>确保网站地图定期更新</li>
                            <li>优化页面加载速度</li>
                            <li>使用HTTPS加密</li>
                            <li>确保移动端友好</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- 关键词分析模态框 -->
    <div id="keywordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>关键词密度分析</h3>
                <span class="close" onclick="closeKeywordModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="keywordInput">关键词</label>
                    <input type="text" id="keywordInput" class="form-control" placeholder="输入要分析的关键词">
                </div>
                <div class="form-group">
                    <label for="contentInput">内容</label>
                    <textarea id="contentInput" class="form-control" rows="5" placeholder="粘贴要分析的内容"></textarea>
                </div>
                <button onclick="analyzeKeyword()" class="btn btn-primary">分析</button>
                <div id="keywordResult" class="keyword-result"></div>
            </div>
        </div>
    </div>
    
    <script>
        function showKeywordAnalyzer() {
            document.getElementById('keywordModal').style.display = 'block';
        }
        
        function closeKeywordModal() {
            document.getElementById('keywordModal').style.display = 'none';
        }
        
        function analyzeKeyword() {
            const keyword = document.getElementById('keywordInput').value;
            const content = document.getElementById('contentInput').value;
            
            if (!keyword || !content) {
                alert('请输入关键词和内容');
                return;
            }
            
            const keywordLower = keyword.toLowerCase();
            const contentLower = content.toLowerCase();
            
            const totalWords = content.split(/\s+/).length;
            const keywordCount = (contentLower.match(new RegExp(keywordLower, 'g')) || []).length;
            const density = ((keywordCount / totalWords) * 100).toFixed(2);
            
            let status = '';
            let statusClass = '';
            
            if (density < 0.5) {
                status = '密度过低';
                statusClass = 'status-low';
            } else if (density > 3) {
                status = '密度过高';
                statusClass = 'status-high';
            } else {
                status = '密度适中';
                statusClass = 'status-good';
            }
            
            const resultHtml = `
                <div class="analysis-result">
                    <h4>分析结果</h4>
                    <div class="result-item">
                        <span>关键词：</span><strong>${keyword}</strong>
                    </div>
                    <div class="result-item">
                        <span>出现次数：</span><strong>${keywordCount}</strong>
                    </div>
                    <div class="result-item">
                        <span>总词数：</span><strong>${totalWords}</strong>
                    </div>
                    <div class="result-item">
                        <span>密度：</span><strong class="${statusClass}">${density}% (${status})</strong>
                    </div>
                </div>
            `;
            
            document.getElementById('keywordResult').innerHTML = resultHtml;
        }
        
        function analyzeLinkStructure() {
            alert('链接分析功能开发中...');
        }
        
        // 点击外部关闭模态框
        window.onclick = function(event) {
            const modal = document.getElementById('keywordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <style>
        .seo-tools,
        .seo-analysis,
        .seo-recommendations {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .seo-tools h3,
        .seo-analysis h3,
        .seo-recommendations h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .tool-card {
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .tool-card h4 {
            color: #495057;
            margin-bottom: 1rem;
        }
        
        .tool-card p {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .tool-card .btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .analysis-table {
            overflow-x: auto;
        }
        
        .analysis-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .analysis-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
        }
        
        .analysis-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .seo-issues {
            max-width: 300px;
        }
        
        .issue {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .issue-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .issue-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .issue-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-good {
            color: #28a745;
            font-weight: 600;
        }
        
        .recommendations {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .recommendation {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }
        
        .recommendation h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .recommendation ul {
            color: #495057;
            margin-left: 1rem;
        }
        
        .recommendation li {
            margin-bottom: 0.5rem;
        }
        
        /* 模态框样式 */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .keyword-result {
            margin-top: 1rem;
        }
        
        .analysis-result {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .result-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .status-low { color: #dc3545; }
        .status-high { color: #fd7e14; }
        .status-good { color: #28a745; }
    </style>
</body>
</html>