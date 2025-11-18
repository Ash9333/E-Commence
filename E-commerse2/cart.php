<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = getDB();
$stmt = $conn->prepare("SELECT c.*, p.name, p.image, ps.seller_price, ps.seller_stock, u.username as seller_name, u.full_name as seller_full_name
                        FROM cart c 
                        INNER JOIN products p ON c.product_id = p.id 
                        INNER JOIN product_sellers ps ON c.product_id = ps.product_id AND c.seller_id = ps.seller_id
                        INNER JOIN users u ON c.seller_id = u.id
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$total = 0;

$page_title = 'Shopping Cart';
include 'includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<h1>Shopping Cart</h1>

<?php if ($cart_items->num_rows > 0): ?>
    <table class="cart-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Seller</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $cart_items->fetch_assoc()): 
                $item_price = $item['seller_price'] ?? $item['price'] ?? 0;
                $item_total = $item_price * $item['quantity'];
                $total += $item_total;
            ?>
                <tr>
                    <td>
                        <img src="<?php echo $item['image'] ? htmlspecialchars($item['image']) : 'images/not_found.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px;"
                             onerror="this.src='images/not_found.jpg'">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($item['seller_full_name']); ?></strong><br>
                        <small style="color: #666;">@<?php echo htmlspecialchars($item['seller_name']); ?></small>
                    </td>
                    <td><?php echo formatPrice($item_price); ?></td>
                    <td>
                        <form action="api/update_cart.php" method="POST" style="display: inline;">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                   min="1" max="<?php echo $item['seller_stock'] ?? 0; ?>" 
                                   class="quantity-input" 
                                   onchange="this.form.submit()">
                        </form>
                    </td>
                    <td><?php echo formatPrice($item_total); ?></td>
                    <td>
                        <a href="api/remove_from_cart.php?id=<?php echo $item['id']; ?>" 
                           class="btn btn-secondary" 
                           onclick="return confirm('Remove this item from cart?')">Remove</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="cart-total">
        <p>Total: <?php echo formatPrice($total); ?></p>
        <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
    </div>
<?php else: ?>
    <p>Your cart is empty. <a href="index.php">Continue Shopping</a></p>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>