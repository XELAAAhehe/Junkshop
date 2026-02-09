<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('includes/db.php');
// NEW: Load the secrets file so we don't hardcode passwords
require 'includes/smtp_config.php'; 

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Allow only admin or super-admin
if ($_SESSION['role'] !== 'super-admin' && $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// ------------------------- HANDLE ACTIONS -------------------------

// Handle Add Staff
if (isset($_POST['final_add_user'])) {
    $full_name = $_POST['full_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
    } else {
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=? AND role IN ('admin','super-admin')");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($admin_password, $admin['password'])) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "staff";
            $verification_code = bin2hex(random_bytes(16));

            try {
                $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, role, is_verified, verification_code) VALUES (?, ?, ?, ?, ?, 0, ?)");
                $stmt->bind_param("ssssss", $full_name, $username, $email, $hashed_password, $role, $verification_code);
                $stmt->execute();

                // Send verification email
                require 'includes/PHPMailer-master/src/Exception.php';
                require 'includes/PHPMailer-master/src/PHPMailer.php';
                require 'includes/PHPMailer-master/src/SMTP.php';
                
                $mail = new PHPMailer(true);

                // USE VARIABLES FROM smtp_config.php
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;

                $mail->setFrom(SMTP_USER, 'Junkshop Admin');
                $mail->addAddress($email, $full_name);
                
                // USE BASE_URL for the link
                $verify_link = BASE_URL . "/verify.php?code=$verification_code";

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Staff Account';
                $mail->Body = "Hi $full_name,<br><br>Your staff account has been created.<br>Please verify your email:<br>
                    <a href='$verify_link'>Verify My Account</a>";
                
                $mail->send();

                $_SESSION['success'] = "Staff account added. Verification email sent.";

            } catch (mysqli_sql_exception | Exception $e) {
                $_SESSION['error'] = "Error creating staff account: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Invalid admin password.";
        }
    }
    header("Location: user_management.php");
    exit();
}

// Handle Delete Staff (Admin or Super-Admin)
if (isset($_POST['final_delete_user'])) {
    $user_id = $_POST['user_id'] ?? null;
    $admin_password = $_POST['admin_password'] ?? '';

    $adminId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=? AND role IN ('admin','super-admin')");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($admin_password, $admin['password'])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='staff'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['success'] = "Staff deleted successfully.";
    } else {
        $_SESSION['error'] = "Invalid admin password.";
    }
}

// Handle Reset Staff Password (Admin or Super-Admin)
if (isset($_POST['final_reset_user'])) {
    $user_id = $_POST['user_id'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    if ($new_password !== $confirm_new_password) {
        $_SESSION['error'] = "New passwords do not match!";
    } else {
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=? AND role IN ('admin','super-admin')");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($admin_password, $admin['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=? AND role='staff'");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            $_SESSION['success'] = "Staff password reset successfully.";
        } else {
            $_SESSION['error'] = "Invalid admin password.";
        }
    }
}

// ------------------------- SUPER-ADMIN ONLY: MANAGE ADMINS -------------------------

// Promote Staff → Admin
if (isset($_POST['promote_user']) && $_SESSION['role'] === 'super-admin') {
    $user_id = $_POST['user_id'] ?? null;
    $admin_password = $_POST['admin_password'] ?? '';

    $superAdminId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=? AND role='super-admin'");
    $stmt->bind_param("i", $superAdminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $superAdmin = $result->fetch_assoc();

    if ($superAdmin && password_verify($admin_password, $superAdmin['password'])) {
        $stmt = $conn->prepare("UPDATE users SET role='admin' WHERE id=? AND role='staff'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['success'] = "Staff promoted to Admin successfully.";
    } else {
        $_SESSION['error'] = "Invalid super-admin password.";
    }
    header("Location: user_management.php");
    exit();
}

// Delete Admin
if (isset($_POST['final_delete_admin']) && $_SESSION['role'] === 'super-admin') {
    $user_id = $_POST['user_id'] ?? null;
    $admin_password = $_POST['admin_password'] ?? '';

    $superAdminId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=? AND role='super-admin'");
    $stmt->bind_param("i", $superAdminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $superAdmin = $result->fetch_assoc();

    if ($superAdmin && password_verify($admin_password, $superAdmin['password'])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='admin'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['success'] = "Admin deleted successfully.";
    } else {
        $_SESSION['error'] = "Invalid super-admin password.";
    }
}

// Reset Admin Password
if (isset($_POST['final_reset_admin']) && $_SESSION['role'] === 'super-admin') {
    $user_id = $_POST['user_id'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';

    if ($new_password !== $confirm_new_password) {
        $_SESSION['error'] = "New passwords do not match!";
    } else {
        $superAdminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=? AND role='super-admin'");
        $stmt->bind_param("i", $superAdminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $superAdmin = $result->fetch_assoc();

        if ($superAdmin && password_verify($admin_password, $superAdmin['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=? AND role='admin'");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            $_SESSION['success'] = "Admin password reset successfully.";
        } else {
            $_SESSION['error'] = "Invalid super-admin password.";
        }
    }
}

// Demote Admin → Staff
if (isset($_POST['demote_user']) && $_SESSION['role'] === 'super-admin') {
    $user_id = $_POST['user_id'] ?? null;
    $admin_password = $_POST['admin_password'] ?? '';

    $superAdminId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=? AND role='super-admin'");
    $stmt->bind_param("i", $superAdminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $superAdmin = $result->fetch_assoc();

    if ($superAdmin && password_verify($admin_password, $superAdmin['password'])) {
        $stmt = $conn->prepare("UPDATE users SET role='staff' WHERE id=? AND role='admin'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $_SESSION['success'] = "Admin demoted to Staff successfully.";
    } else {
        $_SESSION['error'] = "Invalid super-admin password.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management</title>
<link rel="stylesheet" href="css/user_management.css">
</head>
<body>
<header>
    <?php include('includes/navbar.php'); ?>
</header>

<div class="container">
<h2>User Management</h2>

<?php 
if (isset($_SESSION['error'])) { echo "<p class='error'>".$_SESSION['error']."</p>"; unset($_SESSION['error']); }
if (isset($_SESSION['success'])) { echo "<p class='success'>".$_SESSION['success']."</p>"; unset($_SESSION['success']); }
?>

<!-- Add User Form -->
<div id="addUserFormContainer" style="display:none;">
<h3>Add Staff</h3>
<form id="addUserForm" autocomplete="off">
    <input type="text" name="full_name" placeholder="Full Name" required>
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required minlength="6">
    <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
    <div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="addstaff" onclick="openModal('add')">Save Staff</button>
        <button type="button" class="addstaff" onclick="closeAddUserForm()">Cancel</button>
    </div>
</form>
</div>

<!-- Button to Show Form -->
<button type="button" class="addstaff" onclick="openAddUserForm()">Add Staff</button>

<h3>Existing Users</h3>
<div class="table-container">
<table>
<thead>
<tr>
<th>Full Name</th>
<th>Username</th>
<th>Email</th>
<th>Role</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
$result = $conn->query("SELECT id, full_name, username, email, role FROM users");
while ($row = $result->fetch_assoc()):
?>
<tr>
<td data-label='Full Name'><?= htmlspecialchars($row['full_name']) ?></td>
<td data-label='Username'><?= htmlspecialchars($row['username']) ?></td>
<td data-label='Email'><?= htmlspecialchars($row['email']) ?></td>
<td data-label='Role'><?= htmlspecialchars($row['role']) ?></td>
<td data-label='Actions'>

<?php if ($row['role'] === 'staff'): ?>
    <?php if ($_SESSION['role'] === 'super-admin'): ?>
        <button class="primary" onclick="openModal('promote', <?= $row['id'] ?>)">Promote to Admin</button>
    <?php endif; ?>
    <button class='danger' onclick="openModal('delete', <?= $row['id'] ?>)">Delete</button>
    <button class='primary' onclick="openModal('reset', <?= $row['id'] ?>)">Reset Password</button>
<?php elseif ($row['role'] === 'admin' && $_SESSION['role'] === 'super-admin'): ?>
    <button class='danger' onclick="openModal('delete_admin', <?= $row['id'] ?>)">Delete Admin</button>
    <button class='primary' onclick="openModal('reset_admin', <?= $row['id'] ?>)">Reset Password</button>
    <button class='primary' onclick="openModal('demote', <?= $row['id'] ?>)">Demote to Staff</button>
<?php endif; ?>

</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- Modal -->
<div id="passwordModal" class="modal">
<div class="modal-content">
<h3 id="modalTitle">Confirm Admin Password</h3>
<form id="passwordForm" method="POST">
    <input type="hidden" name="action_type" id="action_type">
    <input type="hidden" name="user_id" id="modal_user_id">
    <input type="hidden" name="full_name">
    <input type="hidden" name="username">
    <input type="hidden" name="email">
    <input type="hidden" name="password">
    <input type="hidden" name="confirm_password">
    <div id="resetFields" style="display:none;">
        <input type="password" name="new_password" placeholder="New Password" minlength="6">
        <input type="password" name="confirm_new_password" placeholder="Confirm New Password" minlength="6">
    </div>
    <input type="hidden" id="final_action_input" value="1">
    <input type="password" name="admin_password" placeholder="Enter Admin Password" required>
    <div class="actions">
        <button type="submit" class="btn-confirm">Confirm</button>
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
    </div>
</form>
</div>
</div>

<script>
const modal = document.getElementById('passwordModal');
const form = document.getElementById('passwordForm');
const resetFields = document.getElementById('resetFields');
const actionType = document.getElementById('action_type');
const finalActionInput = document.getElementById('final_action_input');
const modalTitle = document.getElementById('modalTitle');

function openAddUserForm() {
    document.getElementById("addUserFormContainer").style.display = "block";
}

function closeAddUserForm() {
    document.getElementById("addUserFormContainer").style.display = "none";
}

function openModal(action, userId=null) {
    actionType.value = action;
    resetFields.style.display = "none";
    modalTitle.textContent = "Confirm Admin Password";

    if (action === 'delete') {
        form.user_id.value = userId;
        finalActionInput.name = 'final_delete_user';
    } else if (action === 'add') {
        const addForm = document.getElementById('addUserForm');
        if (!addForm.reportValidity()) return;
        if (addForm.password.value !== addForm.confirm_password.value) {
            alert('Passwords do not match.');
            return;
        }
        form.full_name.value = addForm.full_name.value.trim();
        form.username.value = addForm.username.value.trim();
        form.email.value = addForm.email.value.trim();
        form.password.value = addForm.password.value;
        form.confirm_password.value = addForm.confirm_password.value;
        finalActionInput.name = 'final_add_user';
    } else if (action === 'reset') {
        form.user_id.value = userId;
        resetFields.style.display = "block";
        modalTitle.textContent = "Reset User Password";
        finalActionInput.name = 'final_reset_user';
    } else if (action === 'promote') {
        form.user_id.value = userId;
        resetFields.style.display = "none";
        modalTitle.textContent = "Enter Super-Admin Password to Promote Staff";
        finalActionInput.name = 'promote_user';
    } else if (action === 'delete_admin') {
        form.user_id.value = userId;
        resetFields.style.display = "none";
        modalTitle.textContent = "Delete Admin";
        finalActionInput.name = 'final_delete_admin';
    } else if (action === 'reset_admin') {
        form.user_id.value = userId;
        resetFields.style.display = "block";
        modalTitle.textContent = "Reset Admin Password";
        finalActionInput.name = 'final_reset_admin';
    } else if (action === 'demote') {
        form.user_id.value = userId;
        resetFields.style.display = "none";
        modalTitle.textContent = "Demote Admin to Staff";
        finalActionInput.name = 'demote_user';
    }

    modal.classList.add('open');
}

function closeModal() {
    modal.classList.remove('open');
    form.reset();
    resetFields.style.display = "none";
    finalActionInput.removeAttribute('name');
}
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
</script>

<?php include 'loader.html'; ?>
</body>
</html>