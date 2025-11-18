<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    $conn = getDB();
    $stmt = $conn->prepare("SELECT id, full_name, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);

        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
        $stmt->bind_param("is", $user['id'], $token_hash);
        $stmt->execute();

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $basePath = $path !== '' ? $path . '/' : '/';
        $reset_link = $scheme . '://' . $host . $basePath . 'reset_password.php?token=' . urlencode($token);

        $name = $user['full_name'] ?: $user['username'];
        sendPasswordResetEmail($email, $name, $reset_link);
    }

    $success = 'If that email address is registered, a password reset link has been sent.';
}

$page_title = 'Forgot Password';
include 'includes/header.php';
?>

<div class="form-container">
    <h1>Forgot Password</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        <button type="submit" class="btn btn-primary">Send Reset Link</button>
    </form>
    <p style="margin-top: 15px; text-align: center;">
        Remembered your password? <a href="login.php">Go back to login</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
