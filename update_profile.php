<?php
include('includes/db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['full_name'];
$username = $_POST['username'];
$email = $_POST['email'];
$new_password = $_POST['new_password'];

// Update query
if (!empty($new_password)) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, password=? WHERE id=?");
    $stmt->bind_param("ssssi", $full_name, $username, $email, $hashed_password, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=? WHERE id=?");
    $stmt->bind_param("sssi", $full_name, $username, $email, $user_id);
}

if ($stmt->execute()) {
    header("Location: profile.php?success=1");
} else {
    header("Location: profile.php?error=1");
}
?>
