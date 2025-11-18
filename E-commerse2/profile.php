<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDB();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);

        if ($full_name === '' || $username === '' || $email === '') {
            $profile_error = 'Full name, username, and email are required.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->bind_param("ssi", $username, $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $profile_error = 'Username or email is already taken.';
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $full_name, $username, $email, $phone, $address, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $username;
                    $profile_success = 'Profile updated successfully.';
                } else {
                    $profile_error = 'Failed to update profile. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $password_error = 'New passwords do not match.';
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            if (!$user || !password_verify($current_password, $user['password'])) {
                $password_error = 'Current password is incorrect.';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);
                if ($stmt->execute()) {
                    $password_success = 'Password updated successfully.';
                } else {
                    $password_error = 'Failed to update password. Please try again.';
                }
            }
        }
    }
}

$stmt = $conn->prepare("SELECT username, email, full_name, phone, address, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$page_title = 'My Profile';
include 'includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-top: 30px;">
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h2>Account Overview</h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <?php if (!empty($user['phone'])): ?>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <?php endif; ?>
        <?php if (!empty($user['address'])): ?>
            <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
        <?php endif; ?>
        <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
        <p><strong>Member since:</strong> <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
    </div>

    <div>
        <?php if (isset($profile_error)): ?>
            <div class="alert alert-error"><?php echo $profile_error; ?></div>
        <?php endif; ?>
        <?php if (isset($profile_success)): ?>
            <div class="alert alert-success"><?php echo $profile_success; ?></div>
        <?php endif; ?>
        <?php if (isset($password_error)): ?>
            <div class="alert alert-error"><?php echo $password_error; ?></div>
        <?php endif; ?>
        <?php if (isset($password_success)): ?>
            <div class="alert alert-success"><?php echo $password_success; ?></div>
        <?php endif; ?>

        <div class="form-container" style="margin: 0 0 30px 0; max-width: 100%;">
            <h2>Update Profile</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label>Address:</label>
                    <textarea name="address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Save Profile</button>
            </form>
        </div>

        <div class="form-container" style="margin: 0; max-width: 100%;">
            <h2>Change Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password:</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password:</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password:</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-secondary">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
