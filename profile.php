<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer-master/src/Exception.php';
require 'includes/PHPMailer-master/src/PHPMailer.php';
require 'includes/PHPMailer-master/src/SMTP.php';
include('includes/db.php');

// NEW: Load the secrets file
require 'includes/smtp_config.php';

ob_start();

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_dir = "uploads/";

// Fetch user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

/* === AJAX Password Verification === */
if (isset($_POST['verify_password']) && isset($_POST['ajax'])) {
    $current_password = $_POST['current_password'] ?? '';
    $ok = password_verify($current_password, $user['password']);
    $_SESSION['verified_password'] = $ok ? true : false;
    
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => $ok, 
        'message' => $ok ? 'Password verified.' : '❌ Current password is incorrect.'
    ]);
    exit();
}

/* === Email Update === */
if (isset($_POST['update_email']) && isset($_POST['ajax']) && !empty($_SESSION['verified_password'])) {
    $new_email = trim($_POST['email'] ?? '');

    header('Content-Type: application/json');

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'message' => '❌ Invalid email format.']);
        exit();
    }

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_stmt->bind_param("si", $new_email, $user_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($exists) {
        echo json_encode(['ok' => false, 'message' => '❌ Email already in use.']);
        exit();
    }

    // Generate a verification code
    $verification_code = bin2hex(random_bytes(16));

    // Update email and set as unverified
    $update_stmt = $conn->prepare("UPDATE users SET email = ?, is_verified = 0, verification_code = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $new_email, $verification_code, $user_id);

    if ($update_stmt->execute()) {
        unset($_SESSION['verified_password']);

        // Send verification email
        // FIXED: Use BASE_URL from config
        $verification_link = BASE_URL . "/verify.php?code=$verification_code&uid=$user_id";

        $mail = new PHPMailer(true);
        try {
            // FIXED: Use constants from smtp_config.php
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            $mail->setFrom(SMTP_USER, 'Junkshop System');
            $mail->addAddress($new_email, $user['full_name']);
            $mail->isHTML(true);
            $mail->Subject = "Verify Your Email";
            $mail->Body = "Hi {$user['full_name']},<br><br>
                Please click the link below to verify your email:<br>
                <a href='$verification_link'>$verification_link</a><br><br>Thanks!";
            $mail->AltBody = "Hi {$user['full_name']}, verify your email: $verification_link";

            $mail->send();
            echo json_encode(['ok' => true, 'message' => '✅ Email updated! Please check your email to verify.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => "❌ Email updated, but failed to send verification email: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(['ok' => false, 'message' => '❌ Failed to update email.']);
    }

    $update_stmt->close();
    exit();
}

/* === Password Change === */
if (isset($_POST['change_password']) && !empty($_SESSION['verified_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "❌ New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "❌ Password must be at least 6 characters!";
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Password updated successfully!";
            unset($_SESSION['verified_password']);
        } else {
            $_SESSION['error'] = "❌ Failed to update password!";
        }
        $stmt->close();
    }
    header("Location: profile.php");
    exit();
}

/* === Profile Update === */
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $address = trim($_POST['address']);
    $profile_picture = null;
    
    // Validate inputs
    if (empty($full_name) || empty($username)) {
        $_SESSION['error'] = "❌ Full name and username are required!";
        header("Location: profile.php");
        exit();
    }
    
    // Check if username is already taken by another user
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $check_stmt->bind_param("si", $username, $user_id);
    $check_stmt->execute();
    $username_exists = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($username_exists) {
        $_SESSION['error'] = "❌ Username already taken!";
        header("Location: profile.php");
        exit();
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profile_picture']['type'], $allowed)) {
            $_SESSION['error'] = "❌ Only JPG, PNG, GIF files are allowed!";
            header("Location: profile.php");
            exit();
        }
        
        if ($_FILES['profile_picture']['size'] > $max_size) {
            $_SESSION['error'] = "❌ File size must be less than 2MB!";
            header("Location: profile.php");
            exit();
        }
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
        $fileName = time() . "_" . uniqid() . "." . $file_extension;
        $targetFile = $upload_dir . $fileName;
        
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
            // Delete old profile picture if it exists and is not default
            if ($user['profile_picture'] && 
                $user['profile_picture'] !== 'uploads/default-avatar.png' && 
                file_exists($user['profile_picture'])) {
                unlink($user['profile_picture']);
            }
            $profile_picture = $targetFile;
        } else {
            $_SESSION['error'] = "❌ Failed to upload profile picture!";
            header("Location: profile.php");
            exit();
        }
    }
    
    // Update profile
    if ($profile_picture) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, address = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $full_name, $username, $address, $profile_picture, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $username, $address, $user_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Profile updated successfully!";
    } else {
        $_SESSION['error'] = "❌ Failed to update profile!";
    }
    $stmt->close();
    header("Location: profile.php");
    exit();
}

/* === Delete Profile Picture === */
if (isset($_POST['delete_picture'])) {
    $default_pic = "uploads/default-avatar.png";
    
    if ($user['profile_picture'] && 
        $user['profile_picture'] !== $default_pic && 
        file_exists($user['profile_picture'])) {
        unlink($user['profile_picture']);
    }
    
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $default_pic, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Profile picture deleted successfully!";
    } else {
        $_SESSION['error'] = "❌ Failed to delete profile picture!";
    }
    $stmt->close();
    header("Location: profile.php");
    exit();
}

// Refresh user data after any updates
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <header>
        <?php include('includes/navbar.php'); ?>
    </header>

    <div class="profile-container">
            <h1 class="page-title">Profile</h1>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-pic">
                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'uploads/default-avatar.png'; ?>" 
                     alt="Profile Picture">
                <?php if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'uploads/default-avatar.png'): ?>
                    <button type="button" class="btn-danger" onclick="openDeleteModal()">Delete Picture</button>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> 
                    <?php echo htmlspecialchars($user['email']); ?>
                    <?php echo $user['is_verified'] ? 
                        '<span class="verified-badge">✔️ Verified</span>' : 
                        '<span class="unverified-badge">❌ Unverified</span>'; ?>
                </p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                <p><strong>Joined:</strong> <?php echo date("F j, Y", strtotime($user['created_at'])); ?></p>
                <p><strong>Last Login:</strong> 
                    <?php echo $user['last_login'] ? date("F j, Y g:i A", strtotime($user['last_login'])) : 'Never'; ?>
                </p>
                <button class="btn-outline edit-btn" onclick="toggleForm()">Edit Profile</button>
            </div>
        </div>

        <div class="profile-form" id="updateForm" style="display:none;">
            <h3>Update Profile</h3>
            <form method="POST" enctype="multipart/form-data">
                <label>Profile Picture:</label>
                <input type="file" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                
                <label>Full Name:</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                
                <label>Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                
                <div style="display:flex; gap:10px; margin-top:15px; flex-wrap:wrap;">
                    <button type="button" class="btn-primary" onclick="openPasswordModal()">Change Password</button>
                    <button type="button" class="btn-primary" onclick="openEmailModal()">Change Email</button>
                    <button type="submit" name="update_profile" class="btn-outline">Save Changes</button>
                </div>
            </form>
        </div>

        <div id="passwordModal" class="modal">
            <div class="modal-content">
                <form id="verifyForm" autocomplete="off">
                    <h3>Verify Current Password</h3>
                    <input type="password" id="current_password" placeholder="Enter Current Password" required 
                           style="width: 100%; padding: 8px; margin: 10px 0;">
                    <div id="verifyError" class="inline-error" style="display:none;"></div>
                    <div class="actions">
                        <button type="submit" class="btn-confirm">Verify</button>
                        <button type="button" class="btn-cancel" onclick="closePasswordModal()">Cancel</button>
                    </div>
                </form>

                <form id="changeForm" style="display:none;" method="POST">
                    <h3>Set New Password</h3>
                    <input type="password" name="new_password" placeholder="New Password" minlength="6" required
                           style="width: 100%; padding: 8px; margin: 5px 0;">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" minlength="6" required
                           style="width: 100%; padding: 8px; margin: 5px 0;">
                    <div class="actions">
                        <button type="submit" name="change_password" class="btn-confirm">Change Password</button>
                        <button type="button" class="btn-cancel" onclick="closePasswordModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="emailModal" class="modal">
            <div class="modal-content">
                <form id="emailVerifyForm">
                    <h3>Verify Current Password</h3>
                    <input type="password" id="email_current_password" placeholder="Current Password" required
                           style="width: 100%; padding: 8px; margin: 10px 0;">
                    <div id="emailVerifyError" class="inline-error" style="display:none;"></div>
                    <div class="actions">
                        <button type="submit" class="btn-confirm">Verify</button>
                        <button type="button" class="btn-cancel" onclick="closeEmailModal()">Cancel</button>
                    </div>
                </form>

                <form id="emailChangeForm" style="display:none;">
                    <h3>Change Email</h3>
                    <input type="email" id="new_email" placeholder="New Email" required
                           style="width: 100%; padding: 8px; margin: 10px 0;">
                    <div id="emailChangeMessage" style="font-size: 0.9em; margin: 8px 0;"></div>
                    <div class="actions">
                        <button type="submit" class="btn-confirm">Update Email</button>
                        <button type="button" class="btn-cancel" onclick="closeEmailModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h3>Delete Profile Picture?</h3>
                <p>Are you sure you want to delete your profile picture?</p>
                <form method="POST">
                    <div class="actions">
                        <button type="submit" name="delete_picture" class="btn-danger">Yes, Delete</button>
                        <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php include 'loader.html'; ?>
    <script>
        // Toggle profile form
        function toggleForm() {
            const form = document.getElementById('updateForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Password Modal Functions
        function openPasswordModal() {
            document.getElementById('passwordModal').classList.add('open');
            document.getElementById('verifyForm').style.display = 'block';
            document.getElementById('changeForm').style.display = 'none';
            document.getElementById('verifyError').style.display = 'none';
            document.getElementById('current_password').value = '';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('open');
        }

        // Email Modal Functions
        function openEmailModal() {
            document.getElementById('emailModal').classList.add('open');
            document.getElementById('emailVerifyForm').style.display = 'block';
            document.getElementById('emailChangeForm').style.display = 'none';
            document.getElementById('emailVerifyError').style.display = 'none';
            document.getElementById('email_current_password').value = '';
        }

        function closeEmailModal() {
            document.getElementById('emailModal').classList.remove('open');
        }

        // Delete Modal Functions
        function openDeleteModal() {
            document.getElementById('deleteModal').classList.add('open');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('open');
        }

        // Password verification form
        document.getElementById('verifyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const errorDiv = document.getElementById('verifyError');
            const passwordInput = document.getElementById('current_password');
            
            errorDiv.style.display = 'none';
            showLoader(); // 🟢 Show loader manually

            const formData = new FormData();
            formData.append('verify_password', '1');
            formData.append('ajax', '1');
            formData.append('current_password', passwordInput.value);
            
            try {
                const response = await fetch('profile.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                hideLoader(); // 🔵 Hide loader when response received

                if (data.ok) {
                    document.getElementById('verifyForm').style.display = 'none';
                    document.getElementById('changeForm').style.display = 'block';
                } else {
                    errorDiv.textContent = data.message || 'Verification failed.';
                    errorDiv.style.display = 'block';
                    passwordInput.classList.add('shake');
                    setTimeout(() => passwordInput.classList.remove('shake'), 300);
                    passwordInput.focus();
                }
            } catch (error) {
                hideLoader(); // 🔵 Hide loader even if there's an error
                errorDiv.textContent = 'Network error. Please try again.';
                errorDiv.style.display = 'block';
            }
        });



        // Email verification form
        document.getElementById('emailVerifyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const errorDiv = document.getElementById('emailVerifyError');
            const passwordInput = document.getElementById('email_current_password');
            
            errorDiv.style.display = 'none';
            showLoader(); // 🟢 Show loader manually

            const formData = new FormData();
            formData.append('verify_password', '1');
            formData.append('ajax', '1');
            formData.append('current_password', passwordInput.value);
            
            try {
                const response = await fetch('profile.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                hideLoader(); // 🔵 Hide loader when response received

                if (data.ok) {
                    document.getElementById('emailVerifyForm').style.display = 'none';
                    document.getElementById('emailChangeForm').style.display = 'block';
                } else {
                    errorDiv.textContent = data.message || 'Verification failed.';
                    errorDiv.style.display = 'block';
                    passwordInput.classList.add('shake');
                    setTimeout(() => passwordInput.classList.remove('shake'), 300);
                    passwordInput.focus();
                }
            } catch (error) {
                hideLoader(); // 🔵 Always hide loader even on error
                errorDiv.textContent = 'Network error. Please try again.';
                errorDiv.style.display = 'block';
            }
        });


        // Email change form
        document.getElementById('emailChangeForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const messageDiv = document.getElementById('emailChangeMessage');
            const emailInput = document.getElementById('new_email');
            
            messageDiv.style.color = '#555';
            messageDiv.textContent = 'Updating...';
            
            const formData = new FormData();
            formData.append('update_email', '1');
            formData.append('ajax', '1');
            formData.append('email', emailInput.value);
            
            try {
                const response = await fetch('profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                messageDiv.textContent = data.message;
                messageDiv.style.color = data.ok ? 'green' : 'red';
                
                if (data.ok) {
                    setTimeout(() => {
                        closeEmailModal();
                        location.reload(); // Refresh to show updated email
                    }, 2000);
                }
            } catch (error) {
                messageDiv.textContent = 'Network error. Please try again.';
                messageDiv.style.color = 'red';
            }
        });

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('open');
            }
        });
    </script>
</body>
</html>