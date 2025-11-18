<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = getDB();

// Get user orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

$page_title = 'Dashboard';
include 'includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<h1>My Dashboard</h1>

<div style="display: grid; grid-template-columns: 1fr 3fr; gap: 20px;">
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h2>Menu</h2>
        <a href="dashboard.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; display: block; text-align: center;">My Orders</a>
        <a href="profile.php" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px; display: block; text-align: center;">Profile &amp; Settings</a>
        <a href="index.php" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px; display: block; text-align: center;">Continue Shopping</a>
        <a href="cart.php" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px; display: block; text-align: center;">Cart</a>
        <?php if (!isSeller()): ?>
            <a href="become_seller.php" class="btn btn-secondary" style="width: 100%; display: block; text-align: center; background: #ff9800; margin-top: 10px;">Become a Seller</a>
        <?php endif; ?>
    </div>
    
    <div>
        <h2>My Orders</h2>
        <?php if ($orders->num_rows > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td>
                                <span style="padding: 5px 10px; border-radius: 4px; background: #f0f0f0;">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View Details</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no orders yet. <a href="index.php">Start Shopping</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>