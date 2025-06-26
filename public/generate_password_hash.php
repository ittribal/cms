<?php
// generate_password_hash.php - 临时密码哈希生成器
// 用完请立即删除！

// 设置你想要的新密码
$newPassword = '123123'; // <--- 在这里输入你想要设置的新密码

// 使用 PHP 的 password_hash() 函数生成哈希值
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

echo "你设置的新密码是: <strong>" . htmlspecialchars($newPassword) . "</strong><br>";
echo "对应的哈希值是: <strong>" . htmlspecialchars($hashedPassword) . "</strong><br><br>";
echo "请将上面这串哈希值（包括 $2y$10$ 开头的部分）复制到数据库中 admin 用户的密码字段。<br>";
echo "<strong>用完后，务必从服务器删除此文件！</strong>";
?>