<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id'])) {
    $cart_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    $conn = getDB();
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    
    $_SESSION['success'] = 'Item removed from cart';
}

header('Location: ../cart.php');
?>