<?php
// public/templates/public_footer.php - 前台公共底部模板
?>
    </main> <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section about-us">
                    <h3>关于我们</h3>
                    <p><?= esc_html(SITE_DESCRIPTION) ?></p>
                </div>
                <div class="footer-section quick-links">
                    <h3>快速链接</h3>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/public/index.php">首页</a></li>
                        <li><a href="<?= SITE_URL ?>/public/articles.php">所有文章</a></li>
                        <li><a href="<?= SITE_URL ?>/public/privacy.php">隐私政策</a></li>
                        <li><a href="<?= SITE_URL ?>/public/contact.php">联系我们</a></li>
                    </ul>
                </div>
                <div class="footer-section contact-info">
                    <h3>联系方式</h3>
                    <p>邮箱: <a href="mailto:<?= esc_attr(MAIL_FROM_EMAIL) ?>"><?= esc_html(MAIL_FROM_EMAIL) ?></a></p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= esc_html(SITE_TITLE) ?>. All rights reserved.</p>
                <p>Designed with ❤️ by Your Name</p>
            </div>
        </div>
    </footer>

    <script src="<?= SITE_URL ?>/public/assets/js/main.js"></script>
    <?php if (isset($page_specific_js)): ?>
        <script src="<?= SITE_URL ?>/public/assets/js/<?= esc_attr($page_specific_js) ?>.js"></script>
    <?php endif; ?>

    </body>
</html>