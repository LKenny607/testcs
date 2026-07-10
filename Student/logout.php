<?php
session_start();

$_SESSION = array(); // 清空所有 session 变量

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

session_destroy(); // 彻底销毁 session

header("Location: /csproject/Admin/login.php"); // 跳回登录页
exit();