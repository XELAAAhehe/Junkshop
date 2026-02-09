<?php
include('includes/db.php');
session_start();

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id, login_token_expires FROM users WHERE login_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Invalid or expired token.");
}

if (strtotime($user['login_token_expires']) < time()) {
    die("Token expired. Please login again.");
}

// Token valid: log user in
$_SESSION['user_id'] = $user['id'];

// Clear token
$stmt = $conn->prepare("UPDATE users SET login_token=NULL, login_token_expires=NULL WHERE id=?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stmt->close();

$_SESSION['success'] = "Login confirmed! Welcome back.";
header("Location: dashboard.php");
exit();
?>