<?php
// 下载CSV导入模板
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="user_import_template.csv"');
header('Cache-Control: no-cache, must-revalidate');

// 输出BOM以支持中文
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// 写入表头
$headers = [
    'username', 'email', 'real_name', 'phone', 'role', 'password', 'bio'
];
fputcsv($output, $headers);

// 写入示例数据
$examples = [
    ['user1', 'user1@example.com', '张三', '13800138001', 'author', 'password123', '这是作者简介'],
    ['user2', 'user2@example.com', '李四', '13800138002', 'editor', 'password456', '这是编辑简介'],
    ['user3', 'user3@example.com', '王五', '13800138003', 'subscriber', '', '这是订阅者简介']
];

foreach ($examples as $example) {
    fputcsv($output, $example);
}

fclose($output);
?>