<?php
// هش کردن رمز عبور '123456'
$password = '123456';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
echo $hashedPassword;
?>
