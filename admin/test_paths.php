<?php
// admin/test_paths.php - 路径测试页面
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>路径测试</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-item { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .success { background: #d4edda; }
        .error { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>图片上传路径测试</h1>
    
    <div class="test-item">
        <h3>1. 目录结构检查</h3>
        <p><strong>当前文件位置:</strong> <?php echo __FILE__; ?></p>
        <p><strong>当前工作目录:</strong> <?php echo getcwd(); ?></p>
        <p><strong>上级目录:</strong> <?php echo dirname(getcwd()); ?></p>
    </div>
    
    <div class="test-item">
        <h3>2. 上传目录检查</h3>
        <?php
        $uploadDir = '../uploads/editor/';
        $fullPath = realpath($uploadDir);
        ?>
        <p><strong>上传目录路径:</strong> <?php echo $uploadDir; ?></p>
        <p><strong>绝对路径:</strong> <?php echo $fullPath ?: '目录不存在'; ?></p>
        <p><strong>目录是否存在:</strong> <?php echo is_dir($uploadDir) ? '✅ 是' : '❌ 否'; ?></p>
        <p><strong>目录是否可写:</strong> <?php echo is_writable($uploadDir) ? '✅ 是' : '❌ 否'; ?></p>
        
        <?php if (!is_dir($uploadDir)): ?>
        <p style="color: red;">尝试创建目录...</p>
        <?php 
        if (mkdir($uploadDir, 0755, true)) {
            echo '<p style="color: green;">✅ 目录创建成功</p>';
        } else {
            echo '<p style="color: red;">❌ 目录创建失败</p>';
        }
        ?>
        <?php endif; ?>
    </div>
    
    <div class="test-item">
        <h3>3. 测试图片文件</h3>
        <?php
        // 查找现有的图片文件
        $testFiles = [];
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if (strpos($file, 'img_') === 0) {
                    $testFiles[] = $file;
                }
            }
        }
        ?>
        
        <?php if (empty($testFiles)): ?>
        <p>没有找到测试图片文件</p>
        <?php else: ?>
        <p>找到 <?php echo count($testFiles); ?> 个图片文件：</p>
        <?php foreach (array_slice($testFiles, 0, 3) as $file): ?>
            <?php 
            $relativePath = '../uploads/editor/' . $file;
            $absolutePath = $uploadDir . $file;
            ?>
            <div style="margin: 10px 0; padding: 10px; background: #f8f9fa;">
                <p><strong>文件名:</strong> <?php echo $file; ?></p>
                <p><strong>相对路径:</strong> <?php echo $relativePath; ?></p>
                <p><strong>文件是否存在:</strong> <?php echo file_exists($absolutePath) ? '✅ 是' : '❌ 否'; ?></p>
                <p><strong>访问测试:</strong> 
                    <img src="<?php echo $relativePath; ?>" 
                         style="max-width: 100px; max-height: 100px;" 
                         onload="this.style.border='2px solid green'" 
                         onerror="this.style.border='2px solid red'; this.alt='❌ 无法加载';"
                         alt="测试图片">
                </p>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="test-item">
        <h3>4. 简单上传测试</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="test_upload" accept="image/*">
            <button type="submit">测试上传</button>
        </form>
        
        <?php
        if ($_POST && isset($_FILES['test_upload'])) {
            $file = $_FILES['test_upload'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $filename = 'test_' . time() . '.jpg';
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $accessUrl = '../uploads/editor/' . $filename;
                    echo '<div class="success">';
                    echo '<p>✅ 上传成功！</p>';
                    echo '<p><strong>文件路径:</strong> ' . $accessUrl . '</p>';
                    echo '<p><strong>测试访问:</strong> <img src="' . $accessUrl . '" style="max-width: 200px;" alt="上传的图片"></p>';
                    echo '</div>';
                } else {
                    echo '<div class="error">❌ 文件保存失败</div>';
                }
            } else {
                echo '<div class="error">❌ 上传失败，错误代码：' . $file['error'] . '</div>';
            }
        }
        ?>
    </div>
    
    <div class="test-item">
        <h3>5. JavaScript测试</h3>
        <button onclick="testAjaxUpload()">测试AJAX上传</button>
        <div id="ajaxResult"></div>
        
        <script>
        function testAjaxUpload() {
            fetch('upload_image.php', {
                method: 'POST',
                body: new FormData() // 空表单测试
            })
            .then(response => response.text())
            .then(text => {
                document.getElementById('ajaxResult').innerHTML = 
                    '<p><strong>服务器响应:</strong></p><pre>' + text + '</pre>';
            })
            .catch(error => {
                document.getElementById('ajaxResult').innerHTML = 
                    '<p style="color: red;"><strong>请求失败:</strong> ' + error.message + '</p>';
            });
        }
        </script>
    </div>
</body>
</html>