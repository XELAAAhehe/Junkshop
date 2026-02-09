<?php
include('includes/db.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];

    // Find user by username or email
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->bind_param("ss", $login_id, $login_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // For email login, enforce verification
            if (filter_var($login_id, FILTER_VALIDATE_EMAIL) && $user['is_verified'] == 0) {
                $_SESSION['error'] = "Please verify your email before logging in.";
            } else {
                // Password correct and verified (or username login)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Update last login
                $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id=?");
                $update->bind_param("i", $user['id']);
                $update->execute();

                header("Location: dashboard.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid password.";
        }
    } else {
        $_SESSION['error'] = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="logo-circle">
            <img src="junk_manager_logo.png" alt="Junkshop Logo">
        </div>
        <h2>Welcome</h2>
        <p class="subtitle">Login to manage your junkshop system</p>

        <?php if(isset($_SESSION['error'])) { ?>
            <p class="msg error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php } ?>

        <?php if(isset($_SESSION['success'])) { ?>
            <p class="msg success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php } ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="login_id" placeholder="Username or Email" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Login</button>
        </form>

        <div class="extra-links">
            <p><a href="forgot_password.php">Forgot Password?</a></p>
            <p>No account yet? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>
<?php include 'loader.html'; ?>
</body>
</html>

