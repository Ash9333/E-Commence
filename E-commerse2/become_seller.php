<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDB();
$user_id = $_SESSION['user_id'];

// If already seller or admin, just go to seller dashboard if allowed
if (isSeller()) {
    header('Location: seller/index.php');
    exit;
}

$stmt = $conn->prepare("UPDATE users SET role = 'seller' WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();

// Update session role
$_SESSION['role'] = 'seller';
$_SESSION['success'] = 'Your account has been upgraded. You can now sell products.';

header('Location: seller/index.php');
exit;
