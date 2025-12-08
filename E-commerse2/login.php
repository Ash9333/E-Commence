<?php
require_once 'config/database.php';
require_once 'js/includes/functions.php';
require_once 'js/includes/mailer.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if (!empty($user['language'])) {
            setCurrentLanguage($user['language']);
        }

        if (empty($user['language'])) {
            header('Location: choose_language.php');
            exit;
        }

        if ($user['role'] === 'admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

$page_title = 'Login';
include 'js/includes/header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="form-container">
    <h1>Login</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
        
    <form method="POST">
        <div class="form-group">
            <label>Username or Email:</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    <p style="margin-top: 15px; text-align: center;">
        Don't have an account? <a href="register.php">Register here</a>
    </p>
    <p style="margin-top: 5px; text-align: center;">
        <a href="forgot_password.php">Forgot your password?</a>
    </p>
</div>

<?php include 'js/includes/footer.php'; ?>