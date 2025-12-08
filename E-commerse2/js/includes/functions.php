<?php
// Use absolute path to avoid include issues
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once __DIR__ . '/lang.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isSeller() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'seller' || $_SESSION['role'] === 'admin');
}

function getUserCartCount($user_id) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getProducts($category_id = null, $search = null) {
    $conn = getDB();
    $sql = "SELECT DISTINCT p.*, 
            (SELECT MIN(ps.seller_price) FROM product_sellers ps WHERE ps.product_id = p.id) as min_price,
            (SELECT MAX(ps.seller_price) FROM product_sellers ps WHERE ps.product_id = p.id) as max_price
            FROM products p";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($category_id) {
        $sql .= " INNER JOIN product_categories pc ON p.id = pc.product_id";
        $conditions[] = "pc.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    if ($search) {
        $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function getProductSellers($product_id) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT ps.*, u.username, u.full_name 
                            FROM product_sellers ps 
                            INNER JOIN users u ON ps.seller_id = u.id 
                            WHERE ps.product_id = ? 
                            ORDER BY ps.seller_price ASC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getCategories() {
    $conn = getDB();
    $result = $conn->query("SELECT * FROM categories ORDER BY name");
    return $result;
}

function getSellers() {
    $conn = getDB();
    $stmt = $conn->query("SELECT * FROM users WHERE role = 'seller' OR role = 'admin' ORDER BY full_name");
    return $stmt;
}

function cleanupOldCancelledOrders($days = 10) {
    $conn = getDB();
    $days = (int)$days;
    if ($days <= 0) {
        $days = 10;
    }
    $days_sql = $days;
    $conn->query("UPDATE orders SET cancelled_at = IFNULL(cancelled_at, created_at) WHERE status = 'cancelled' AND cancelled_at IS NULL");
    $conn->query("DELETE FROM orders WHERE status = 'cancelled' AND cancelled_at IS NOT NULL AND cancelled_at < DATE_SUB(NOW(), INTERVAL $days_sql DAY)");
}
?>