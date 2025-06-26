<?php
// admin/system/seo.php - SEO优化工具

// --- 修复点：确保 config.php 首先被引入，并使用 $_SERVER['DOCUMENT_ROOT'] ---
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php'; 

// 引入其他核心类和函数，它们现在可以安全地使用 ABSPATH 了
require_once ABSPATH . 'includes/functions.php'; 
require_once ABSPATH . 'includes/Database.php';
require_once ABSPATH . 'includes/Auth.php';

$db = Database::getInstance();
$auth = Auth::getInstance();

// 检查登录和权限
$auth->requirePermission('system.seo', '您没有权限访问 SEO 优化页面。'); // 假设有 system.seo 权限

// SEO分析类
class SEOAnalyzer 
{
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // 分析文章SEO
    public function analyzeArticles() {
        // 获取所有已发布的文章进行分析
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
                'issues' => $this->findSEOIssues($article) // 调用私有方法查找问题
            ];
        }
        
        return $analysis;
    }
    
    // 查找单篇文章的SEO问题
    private function findSEOIssues($article) {
        $issues = [];
        
        // 标题长度检查 (建议10-60字符)
        $title_len = mb_strlen($article['title'], 'UTF-8');
        if ($title_len < 10) {
            $issues[] = ['type' => 'warning', 'message' => '文章标题过短（建议10-60字符）'];
        } elseif ($title_len > 60) {
            $issues[] = ['type' => 'warning', 'message' => '文章标题过长（建议10-60字符）'];
        }
        
        // Meta标题检查 (建议与文章标题不同，但通常也是50-60字符)
        $meta_title = $article['meta_title'] ?? '';
        $meta_title_len = mb_strlen($meta_title, 'UTF-8');
        if (empty($meta_title)) {
            $issues[] = ['type' => 'error', 'message' => '缺少 SEO 标题'];
        } elseif ($meta_title_len > 60) {
            $issues[] = ['type' => 'warning', 'message' => 'SEO 标题过长（建议60字符以内）'];
        }
        
        // Meta描述检查 (建议120-160字符)
        $meta_description = $article['meta_description'] ?? '';
        $meta_description_len = mb_strlen($meta_description, 'UTF-8');
        if (empty($meta_description)) {
            $issues[] = ['type' => 'error', 'message' => '缺少 SEO 描述'];
        } elseif ($meta_description_len < 120) {
            $issues[] = ['type' => 'warning', 'message' => 'SEO 描述过短（建议120-160字符）'];
        } elseif ($meta_description_len > 160) {
            $issues[] = ['type' => 'warning', 'message' => 'SEO 描述过长（建议120-160字符）'];
        }
        
        // URL友好性检查 (slug 长度，是否包含特殊字符等)
        if (mb_strlen($article['slug'], 'UTF-8') < 3) {
            $issues[] = ['type' => 'warning', 'message' => 'URL 别名过短'];
        }
        
        // 内容长度检查 (至少300字符)
        $content_plain_text = strip_tags($article['content']); // 移除 HTML 标签
        $content_len = mb_strlen($content_plain_text, 'UTF-8');
        if ($content_len < 300) {
            $issues[] = ['type' => 'warning', 'message' => '文章内容过短（建议至少300字符）'];
        }
        
        // 摘要检查
        if (empty($article['excerpt'])) {
            $issues[] = ['type' => 'info', 'message' => '建议添加文章摘要'];
        }
        
        // 图片 Alt 属性检查 (需要解析 HTML 内容)
        // 这是一个更复杂的检查，此处简化，你可以扩展
        if (preg_match('/<img[^>]*src=["\']([^"\']+)["\'][^>]*alt=["\'](["\'])["\'][^>]*>/i', $article['content'])) {
            $issues[] = ['type' => 'warning', 'message' => '文章中存在缺少 Alt 属性的图片'];
        }

        return $issues;
    }
    
    /**
     * 分析关键词密度
     * @param string $content 待分析的内容
     * @param string $keyword 要分析的关键词
     * @return array 关键词密度分析结果
     */
    public function analyzeKeywordDensity($content, $keyword) {
        $content = mb_strtolower(strip_tags($content), 'UTF-8'); // 移除HTML标签并转小写
        $keyword = mb_strtolower($keyword, 'UTF-8');
        
        // 将内容拆分成单词，考虑中文词汇
        // 简单的中文分词，可以考虑更专业的库
        preg_match_all('/[\p{L}\p{N}]+/u', $content, $matches);
        $words = $matches[0];
        
        $totalWords = count($words);
        
        // 计算关键词出现次数 (精确匹配)
        $keywordCount = 0;
        foreach ($words as $word) {
            if ($word === $keyword) {
                $keywordCount++;
            }
        }
        
        $density = $totalWords > 0 ? ($keywordCount / $totalWords) * 100 : 0;
        
        return [
            'keyword' => $keyword,
            'count' => $keywordCount,
            'total_words' => $totalWords,
            'density' => round($density, 2)
        ];
    }
    
    /**
     * 生成网站地图数据结构 (用于 sitemap.xml)
     * @return array URL 数组
     */
    public function generateSitemapData() {
        $urls = [];
        
        // 添加主页
        $urls[] = [
            'loc' => SITE_URL . '/public/index.php',
            'lastmod' => date('c'),
            'changefreq' => 'daily',
            'priority' => '1.0'
        ];
        
        // 添加所有已发布的文章页面
        $articles = $this->db->fetchAll("
            SELECT slug, updated_at 
            FROM articles 
            WHERE status = 'published' 
            ORDER BY updated_at DESC
        ");
        
        foreach ($articles as $article) {
            $urls[] = [
                'loc' => SITE_URL . '/public/article.php?slug=' . esc_attr($article['slug']),
                'lastmod' => date('c', strtotime($article['updated_at'])),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        }
        
        // 添加所有活跃的分类页面
        $categories = $this->db->fetchAll("
            SELECT slug 
            FROM categories 
            WHERE status = 'active'
        ");
        
        foreach ($categories as $category) {
            $urls[] = [
                'loc' => SITE_URL . '/public/category.php?slug=' . esc_attr($category['slug']),
                'lastmod' => date('c'), // 分类页面的更新时间可以从最新文章或手动维护
                'changefreq' => 'weekly',
                'priority' => '0.6'
            ];
        }

        // 添加所有活跃的标签页面
        $tags = $this->db->fetchAll("
            SELECT slug 
            FROM tags 
            ORDER BY name ASC
        ");
        
        foreach ($tags as $tag) {
            $urls[] = [
                'loc' => SITE_URL . '/public/tag.php?slug=' . esc_attr($tag['slug']),
                'lastmod' => date('c'), // 标签页面的更新时间可以从最新文章或手动维护
                'changefreq' => 'weekly',
                'priority' => '0.5'
            ];
        }
        
        return $urls;
    }
}

$seoAnalyzer = new SEOAnalyzer($db);
$pageTitle = 'SEO优化'; // 页面标题

// 处理 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('CSRF 验证失败，请刷新页面重试。', 'error');
        safe_redirect(SITE_URL . '/admin/system/seo.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate_sitemap':
            $auth->requirePermission('system.seo', '您没有权限生成网站地图。');
            try {
                $sitemapData = $seoAnalyzer->generateSitemapData();
                $sitemapXml = generateSitemapXML($sitemapData); // 调用函数生成 XML
                
                $sitemap_path = ABSPATH . 'public/sitemap.xml';
                ensure_directory_exists(dirname($sitemap_path)); // 确保目录存在
                file_put_contents($sitemap_path, $sitemapXml);
                
                $auth->logAction($auth->getCurrentUser()['id'], '生成网站地图', 'sitemap.xml');
                set_flash_message('网站地图生成成功！', 'success');
            } catch (Exception $e) {
                set_flash_message('网站地图生成失败：' . $e->getMessage(), 'error');
            }
            safe_redirect(SITE_URL . '/admin/system/seo.php');
            break;
            
        case 'generate_robots':
            $auth->requirePermission('system.seo', '您没有权限生成 robots.txt。');
            try {
                $robotsContent = generateRobotsTxt($db); // 调用函数生成 robots.txt 内容
                
                $robots_path = ABSPATH . 'public/robots.txt';
                ensure_directory_exists(dirname($robots_path));
                file_put_contents($robots_path, $robotsContent);
                
                $auth->logAction($auth->getCurrentUser()['id'], '生成 robots.txt', 'robots.txt');
                set_flash_message('robots.txt 文件生成成功！', 'success');
            } catch (Exception $e) {
                set_flash_message('robots.txt 生成失败：' . $e->getMessage(), 'error');
            }
            safe_redirect(SITE_URL . '/admin/system/seo.php');
            break;
            
        case 'analyze_keyword_ajax': // 用于 AJAX 调用的关键词分析
            // 权限检查 (如果需要)
            $content = $_POST['content'] ?? '';
            $keyword = $_POST['keyword'] ?? '';
            if (empty($content) || empty($keyword)) {
                json_response(['success' => false, 'message' => '关键词和内容不能为空。'], 400);
            }
            $result = $seoAnalyzer->analyzeKeywordDensity($content, $keyword);
            json_response(['success' => true, 'result' => $result]);
            break;
    }
}

// 获取 SEO 分析结果
$seoAnalysis = $seoAnalyzer->analyzeArticles();

// 辅助函数：生成网站地图 XML 格式 (已移入 functions.php 或作为独立辅助函数)
function generateSitemapXML($urls) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($urls as $url) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . esc_url($url['loc']) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
        $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
        $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
        $xml .= '  </url>' . "\n";
    }
    
    $xml .= '</urlset>';
    return $xml;
}

// 辅助函数：生成 robots.txt 内容 (已移入 functions.php 或作为独立辅助函数)
function generateRobotsTxt($db_instance) { // 接收 $db 实例
    // 尝试从 site_settings 获取 robots_txt 的自定义内容
    $robotsContent = $db_instance->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'robots_txt'")['setting_value'] ?? '';
    
    if (empty($robotsContent)) {
        // 如果没有自定义内容，生成一个默认的
        $robotsContent = "User-agent: *\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /includes/\n";
        $robotsContent .= "Disallow: /logs/\n";
        $robotsContent .= "Disallow: /backups/\n\n";
        $robotsContent .= "Sitemap: " . SITE_URL . "/public/sitemap.xml\n"; // 依赖 SITE_URL
    }
    
    return $robotsContent;
}

// 获取并显示闪存消息
$flash_message = get_flash_message();

// 引入后台头部模板
include ABSPATH . 'templates/admin_header.php'; 
?>

<main class="content">
    <div class="page-header">
        <h1 class="page-title">SEO 优化工具</h1>
        <div class="page-actions">
            </div>
    </div>
    
    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_message['type'] ?>">
            <i class="fas fa-<?= $flash_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash_message['message']) ?>
        </div>
    <?php endif; ?>
    
    <div class="content-card mb-4">
        <div class="card-header">
            <h3>SEO 工具</h3>
        </div>
        <div class="card-body">
            <div class="tools-grid">
                <div class="tool-card">
                    <h4>🗺️ 网站地图 (sitemap.xml)</h4>
                    <p>生成 XML 格式的网站地图，帮助搜索引擎索引您的网站内容。</p>
                    <form method="POST" style="display: inline-block; margin-right: 10px;">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="generate_sitemap">
                        <button type="submit" class="btn btn-primary btn-sm">生成网站地图</button>
                    </form>
                    <?php if (file_exists(ABSPATH . 'public/sitemap.xml')): ?>
                        <a href="<?= SITE_URL ?>/public/sitemap.xml" target="_blank" class="btn btn-info btn-sm">查看地图</a>
                    <?php endif; ?>
                </div>
                
                <div class="tool-card">
                    <h4>🤖 Robots.txt</h4>
                    <p>生成 robots.txt 文件，控制搜索引擎爬虫对网站内容的抓取行为。</p>
                    <form method="POST" style="display: inline-block; margin-right: 10px;">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="generate_robots">
                        <button type="submit" class="btn btn-success btn-sm">生成 Robots.txt</button>
                    </form>
                    <?php if (file_exists(ABSPATH . 'public/robots.txt')): ?>
                        <a href="<?= SITE_URL ?>/public/robots.txt" target="_blank" class="btn btn-info btn-sm">查看文件</a>
                    <?php endif; ?>
                </div>
                
                <div class="tool-card">
                    <h4>📊 关键词分析</h4>
                    <p>分析文章内容的关键词密度和分布，优化内容相关性。</p>
                    <button onclick="showKeywordAnalyzerModal()" class="btn btn-warning btn-sm">关键词分析</button>
                </div>
                
                <div class="tool-card">
                    <h4>🔗 内链分析</h4>
                    <p>检查网站内部链接结构，发现孤立页面和优化链接权重。</p>
                    <button onclick="alert('内链分析功能开发中...') /* 实际功能需要开发 */" class="btn btn-secondary btn-sm">内链分析</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-card mb-4">
        <div class="card-header">
            <h3>文章 SEO 问题分析</h3>
        </div>
        <div class="card-body">
            <div class="analysis-table table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>文章标题</th>
                            <th>URL 别名</th>
                            <th>浏览量</th>
                            <th>SEO 问题</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($seoAnalysis)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted p-3">暂无文章可供分析。</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($seoAnalysis as $analysis): ?>
                            <tr>
                                <td>
                                    <strong><?= esc_html($analysis['title']) ?></strong>
                                </td>
                                <td>
                                    <code><?= esc_html($analysis['slug']) ?></code>
                                </td>
                                <td><?= number_format($analysis['views']) ?></td>
                                <td>
                                    <?php if (empty($analysis['issues'])): ?>
                                        <span class="badge badge-success">✅ 良好</span>
                                    <?php else: ?>
                                        <div class="seo-issues">
                                            <?php foreach ($analysis['issues'] as $issue): ?>
                                                <span class="badge badge-<?= esc_attr($issue['type']) ?>">
                                                    <?= esc_html($issue['message']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= SITE_URL ?>/admin/content/article_form.php?id=<?= $analysis['id'] ?>" 
                                           class="btn btn-primary btn-sm">编辑</a>
                                        <a href="<?= SITE_URL ?>/public/article.php?slug=<?= esc_attr($analysis['slug']) ?>" 
                                           target="_blank" class="btn btn-info btn-sm">查看</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="content-card">
        <div class="card-header">
            <h3>SEO 优化建议</h3>
        </div>
        <div class="card-body">
            <div class="recommendations-grid">
                <div class="recommendation-item">
                    <h4>📝 内容优化</h4>
                    <ul>
                        <li>确保每篇文章都有独特的标题和描述。</li>
                        <li>文章内容长度建议在 300 字以上。</li>
                        <li>使用结构化的标题层次（H1, H2, H3）来组织内容。</li>
                        <li>在文章中添加相关的内部链接和外部链接。</li>
                    </ul>
                </div>
                
                <div class="recommendation-item">
                    <h4>🔍 关键词策略</h4>
                    <ul>
                        <li>关键词在文章中的密度保持在 1-3% 之间。</li>
                        <li>在标题、SEO 描述和文章内容中自然地使用关键词。</li>
                        <li>使用长尾关键词来提高在特定搜索中的排名机会。</li>
                        <li>避免关键词堆砌，这可能导致搜索引擎惩罚。</li>
                    </ul>
                </div>
                
                <div class="recommendation-item">
                    <h4>🔗 技术 SEO</h4>
                    <ul>
                        <li>确保网站地图 (`sitemap.xml`) 定期更新并提交给搜索引擎。</li>
                        <li>优化页面加载速度，例如压缩图片和启用浏览器缓存。</li>
                        <li>使用 HTTPS 加密您的网站以提高安全性。</li>
                        <li>确保网站对移动设备友好，提供良好的移动端体验。</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include ABSPATH . 'templates/admin_footer.php'; // 引入后台底部模板 ?>

<div id="keywordAnalyzerModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>关键词密度分析</h3>
            <button type="button" class="modal-close" onclick="closeModal('keywordAnalyzerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="keywordInput">关键词</label>
                <input type="text" id="keywordInput" class="form-control" placeholder="输入要分析的关键词">
            </div>
            <div class="form-group">
                <label for="contentInput">内容</label>
                <textarea id="contentInput" class="form-control" rows="8" placeholder="粘贴要分析的文章内容"></textarea>
            </div>
            <button type="button" onclick="analyzeKeyword()" class="btn btn-primary">
                <i class="fas fa-search"></i> 分析
            </button>
            <div id="keywordResult" class="keyword-result mt-3">
                </div>
        </div>
    </div>
</div>

<style>
/* 样式已从 admin/assets/css/settings.css 和 admin/assets/css/admin.css 加载 */
/* 这里仅为方便展示特定样式 */
.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.tool-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* 按钮靠下 */
}

.tool-card h4 {
    font-size: 1.1rem;
    margin-bottom: 0.8rem;
    color: #2c3e50;
}

.tool-card p {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 1rem;
    flex-grow: 1; /* 占据剩余空间 */
}

.tool-card .btn {
    margin-right: 0.5rem; /* 按钮之间的间距 */
    margin-top: 1rem; /* 与p标签的间距 */
}

.analysis-table table {
    min-width: 800px; /* 确保在小屏幕下能滚动 */
}

.seo-issues .badge {
    margin-right: 5px;
    margin-bottom: 5px;
}

.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.recommendation-item {
    background: #f8f9fa;
    border-left: 4px solid #3498db;
    border-radius: 0 8px 8px 0;
    padding: 1.5rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.recommendation-item h4 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.recommendation-item ul {
    list-style: disc;
    margin-left: 1.5rem;
    color: #495057;
    font-size: 0.95rem;
}

.recommendation-item li {
    margin-bottom: 0.5rem;
}

/* 关键词分析模态框特定样式 */
.keyword-result {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.keyword-result h4 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.keyword-result .result-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px dashed #eee;
}

.keyword-result .result-item:last-child {
    border-bottom: none;
}

.keyword-result .result-item span,
.keyword-result .result-item strong {
    color: #495057;
    font-size: 1rem;
}

.keyword-result .result-item strong.status-low { color: #e74c3c; }
.keyword-result .result-item strong.status-fair { color: #f39c12; }
.keyword-result .result-item strong.status-good { color: #27ae60; }


/* 响应式 */
@media (max-width: 768px) {
    .tools-grid, .recommendations-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// JavaScript 逻辑将由 admin/assets/js/seo.js 文件提供
// 这是一个占位符，表示该页面的 JS 逻辑会在这里加载

// 示例函数，实际由 seo.js 提供
function showKeywordAnalyzerModal() {
    // 假设 openModal 在 common.js 中定义
    if (typeof openModal === 'function') {
        openModal('keywordAnalyzerModal'); 
    } else {
        alert('模态框功能未加载。');
    }
    // 清空上次结果
    document.getElementById('keywordInput').value = '';
    document.getElementById('contentInput').value = '';
    document.getElementById('keywordResult').innerHTML = '';
}

function analyzeKeyword() {
    const keywordInput = document.getElementById('keywordInput');
    const contentInput = document.getElementById('contentInput');
    const keywordResultDiv = document.getElementById('keywordResult');

    const keyword = keywordInput.value.trim();
    const content = contentInput.value.trim();

    if (!keyword || !content) {
        // 假设 showToast 在 common.js 中定义
        if (typeof showToast === 'function') {
            showToast('关键词和内容不能为空！', 'error'); 
        } else {
            alert('关键词和内容不能为空！');
        }
        return;
    }

    // 显示加载状态
    keywordResultDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> 正在分析...</div>';

    // 通过 AJAX 调用后端进行分析
    // 假设后端接口在 admin/system/seo.php 的 action=analyze_keyword_ajax
    // 假设 apiFetch 在 common.js 中定义
    if (typeof apiFetch !== 'function') {
        alert('API Fetch 功能未加载，请检查 common.js');
        keywordResultDiv.innerHTML = '<p class="text-danger">API Fetch 功能未加载。</p>';
        return;
    }

    apiFetch('<?= SITE_URL ?>/admin/system/seo.php', { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'analyze_keyword_ajax',
            keyword: keyword,
            content: content,
            csrf_token: '<?= generate_csrf_token() ?>' // 确保 CSRF token 被发送
        })
    })
    .then(data => {
        if (data.success) {
            const result = data.result;
            let status = '';
            let statusClass = '';

            if (result.density < 0.5) {
                status = '密度过低';
                statusClass = 'status-low';
            } else if (result.density > 3) {
                status = '密度过高';
                statusClass = 'status-high';
            } else {
                status = '密度适中';
                statusClass = 'status-good';
            }

            keywordResultDiv.innerHTML = `
                <h4>分析结果</h4>
                <div class="result-item">
                    <span>关键词：</span><strong>${esc_html(result.keyword)}</strong>
                </div>
                <div class="result-item">
                    <span>出现次数：</span><strong>${result.count}</strong>
                </div>
                <div class="result-item">
                    <span>总词数：</span><strong>${result.total_words}</strong>
                </div>
                <div class="result-item">
                    <span>密度：</span><strong class="${statusClass}">${result.density}% (${status})</strong>
                </div>
            `;
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || '分析失败', 'error');
            } else {
                alert(data.message || '分析失败');
            }
            keywordResultDiv.innerHTML = '<p class="text-danger">分析失败。</p>';
        }
    })
    .catch(error => {
        if (typeof showToast === 'function') {
            showToast('分析时发生网络错误。', 'error');
        } else {
            alert('分析时发生网络错误。');
        }
        keywordResultDiv.innerHTML = '<p class="text-danger">网络错误，请重试。</p>';
        console.error('Keyword analysis error:', error);
    });
}
</script>