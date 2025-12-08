<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $seller_id = (int)$_POST['seller_id'];
    $quantity = (int)$_POST['quantity'];
    
    $conn = getDB();
    
    // Check if product-seller combination exists and has stock
    $stmt = $conn->prepare("SELECT ps.seller_stock, ps.seller_price 
                            FROM product_sellers ps 
                            WHERE ps.product_id = ? AND ps.seller_id = ?");
    $stmt->bind_param("ii", $product_id, $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product_seller = $result->fetch_assoc();
    
    if (!$product_seller || $product_seller['seller_stock'] < $quantity) {
        $_SESSION['error'] = 'Insufficient stock';
        header('Location: ../product.php?id=' . $product_id);
        exit;
    }
    
    // Check if item already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND seller_id = ?");
    $stmt->bind_param("iii", $user_id, $product_id, $seller_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        $new_quantity = $existing['quantity'] + $quantity;
        if ($new_quantity > $product_seller['seller_stock']) {
            $_SESSION['error'] = 'Cannot add more items. Stock limit reached.';
        } else {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $existing['id']);
            $stmt->execute();
            $_SESSION['success'] = 'Cart updated successfully';
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, seller_id, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $user_id, $product_id, $seller_id, $quantity);
        $stmt->execute();
        $_SESSION['success'] = 'Product added to cart';
    }
    
    header('Location: ../product.php?id=' . $product_id);
} else {
    header('Location: ../index.php');
}
?>