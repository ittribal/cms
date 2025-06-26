<?php
// 创建会话目录
$sessionPath = __DIR__ . '/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
    echo "✅ 会话目录已创建<br>";
}

// 设置权限
chmod($sessionPath, 0755);
echo "✅ 权限已设置<br>";

// 测试会话
session_save_path($sessionPath);
session_start();
$_SESSION['test'] = 'OK';

echo "✅ 会话修复完成！<br>";
echo "会话保存路径：" . session_save_path() . "<br>";
echo "会话ID：" . session_id() . "<br>";
?>