<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];
    
    $conn = getDB();
    
    // Verify cart item belongs to user and get seller stock
    $stmt = $conn->prepare("SELECT c.*, ps.seller_stock 
                            FROM cart c 
                            INNER JOIN product_sellers ps ON c.product_id = ps.product_id AND c.seller_id = ps.seller_id
                            WHERE c.id = ? AND c.user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    
    if ($item && $quantity <= ($item['seller_stock'] ?? 0) && $quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $cart_id);
        $stmt->execute();
        $_SESSION['success'] = 'Cart updated';
    } else {
        $_SESSION['error'] = 'Invalid quantity or insufficient stock';
    }
    
    header('Location: ../cart.php');
} else {
    header('Location: ../index.php');
}
?>