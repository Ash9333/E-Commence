<?php
require_once __DIR__ . '/config/database.php';
require_once 'js/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = getDB();

// Get cart items with seller info
$stmt = $conn->prepare("SELECT c.*, p.name, ps.seller_price, ps.seller_stock 
                        FROM cart c 
                        INNER JOIN products p ON c.product_id = p.id 
                        INNER JOIN product_sellers ps ON c.product_id = ps.product_id AND c.seller_id = ps.seller_id
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

if ($cart_items->num_rows == 0) {
    header('Location: cart.php');
    exit;
}

$total = 0;
$items_array = [];

while ($item = $cart_items->fetch_assoc()) {
    $item_price = $item['seller_price'] ?? 0;
    $item_total = $item_price * $item['quantity'];
    $total += $item_total;
    $items_array[] = $item;
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize($_POST['shipping_address']);
    
    // Validate stock before checkout
    $all_available = true;
    foreach ($items_array as $item) {
        if ($item['quantity'] > ($item['seller_stock'] ?? 0)) {
            $all_available = false;
            break;
        }
    }
    
    if (!$all_available) {
        $_SESSION['error'] = 'Some items are out of stock. Please update your cart.';
    } else {
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $user_id, $total, $shipping_address);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Create order items and update seller stock
        foreach ($items_array as $item) {
            $item_price = $item['seller_price'] ?? 0;
            $item_total = $item_price * $item['quantity'];
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, seller_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidi", $order_id, $item['product_id'], $item['seller_id'], $item['quantity'], $item_price);
            $stmt->execute();
            
            // Update seller stock in product_sellers table
            $new_stock = ($item['seller_stock'] ?? 0) - $item['quantity'];
            $stmt = $conn->prepare("UPDATE product_sellers SET seller_stock = ? WHERE product_id = ? AND seller_id = ?");
            $stmt->bind_param("iii", $new_stock, $item['product_id'], $item['seller_id']);
            $stmt->execute();
        }
        
        // Clear cart
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $_SESSION['success'] = 'Order placed successfully! Order ID: #' . $order_id;
        header('Location: dashboard.php');
        exit;
    }
}

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$page_title = 'Checkout';
include 'js/includes/header.php';

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<h1>Checkout</h1>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <div>
        <div class="form-container">
            <h2>Shipping Information</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Shipping Address:</label>
                    <textarea name="shipping_address" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Place Order</button>
            </form>
        </div>
    </div>
    
    <div>
        <div style="background: white; padding: 20px; border-radius: 8px;">
            <h2>Order Summary</h2>
            <?php foreach ($items_array as $item): 
                $item_price = $item['seller_price'] ?? 0;
                $item_total = $item_price * $item['quantity'];
            ?>
                <div style="padding: 10px 0; border-bottom: 1px solid #eee;">
                    <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                    <small>Seller: <?php 
                        $seller_stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                        $seller_stmt->bind_param("i", $item['seller_id']);
                        $seller_stmt->execute();
                        $seller = $seller_stmt->get_result()->fetch_assoc();
                        echo htmlspecialchars($seller['full_name'] ?? 'Unknown');
                    ?></small><br>
                    <?php echo $item['quantity']; ?> x <?php echo formatPrice($item_price); ?> = <?php echo formatPrice($item_total); ?>
                </div>
            <?php endforeach; ?>
            <div style="margin-top: 20px; font-size: 1.3rem; font-weight: bold;">
                Total: <?php echo formatPrice($total); ?>
            </div>
        </div>
    </div>
</div>

<?php include 'js/includes/footer.php'; ?>