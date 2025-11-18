<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$token_valid = false;
$user_id = null;

if ($token) {
    $token_hash = hash('sha256', $token);
    $conn = getDB();
    $stmt = $conn->prepare("SELECT pr.user_id, u.email FROM password_resets pr INNER JOIN users u ON pr.user_id = u.id WHERE pr.token_hash = ? AND pr.expires_at > NOW() AND pr.used = 0 LIMIT 1");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();

    if ($reset) {
        $token_valid = true;
        $user_id = (int)$reset['user_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $token_hash = hash('sha256', $token);
        $conn = getDB();
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW() AND used = 0 LIMIT 1");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();

        if ($reset) {
            $user_id = (int)$reset['user_id'];
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token_hash = ?");
            $stmt->bind_param("s", $token_hash);
            $stmt->execute();

            $_SESSION['success'] = 'Your password has been reset. You can now login with your new password.';
            header('Location: login.php');
            exit;
        } else {
            $error = 'This reset link is invalid or has expired.';
        }
    }
}

$page_title = 'Reset Password';
include 'includes/header.php';
?>

<div class="form-container">
    <h1>Reset Password</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!$token && !isset($_POST['token'])): ?>
        <p>This reset link is invalid. Please request a new one from the <a href="forgot_password.php">Forgot Password</a> page.</p>
    <?php elseif (!$token_valid && !isset($error) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <p>This reset link is invalid or has expired. Please request a new one from the <a href="forgot_password.php">Forgot Password</a> page.</p>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ($_POST['token'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
            <div class="form-group">
                <label>New Password:</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password:</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
