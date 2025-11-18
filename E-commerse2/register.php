<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $conn = getDB();
        
        // Check if username or email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $phone);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Registration successful! Please login.';
                header('Location: login.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$page_title = 'Register';
include 'includes/header.php';
?>

<div class="form-container">
    <h1>Register</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Phone:</label>
            <input type="text" name="phone">
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
    <p style="margin-top: 15px; text-align: center;">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>