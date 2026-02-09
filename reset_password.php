<?php
include('includes/db.php');
session_start();

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $tokenHash = hash("sha256", $token);

    $stmt = $conn->prepare("SELECT id, reset_token, reset_expires FROM users WHERE reset_token=? LIMIT 1");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $new_password = $_POST["new_password"];
            $confirm_password = $_POST["confirm_password"];

            if ($new_password === $confirm_password) {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
                $stmt->bind_param("si", $hashedPassword, $user['id']);

                if ($stmt->execute()) {
                    header("Location: login.php?reset=success");
                    exit;
                } else {
                    $error = "Error updating password. Please try again.";
                }
            } else {
                $error = "Passwords do not match!";
            }
        }
    } else {
        die("Invalid or expired token.");
    }
} else {
    die("No token provided.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/reset_password.css">
</head>
<body>
    <div class="reset-container">
        <div class="logo-circle">
            <img src="junk_manager_logo.png" alt="Logo">
        </div>
        <h2>Enter New Password</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Reset Password</button>
        </form>
    </div>
<?php include 'loader.html'; ?>
</body>
</html>
