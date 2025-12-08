<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';
require_once '../js/includes/mailer.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$conn = getDB();
cleanupOldCancelledOrders(10);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize($_POST['status']);
    
    $stmt = $conn->prepare("SELECT o.status, o.total_amount, u.full_name, u.email FROM orders o INNER JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $orderInfo = $stmt->get_result()->fetch_assoc();

    $sendCancelEmail = false;
    if ($orderInfo && $orderInfo['status'] !== 'cancelled' && $status === 'cancelled') {
        $sendCancelEmail = true;
    }

    if ($status === 'cancelled') {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, cancelled_at = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, cancelled_at = NULL WHERE id = ?");
    }
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();

    if ($sendCancelEmail) {
        sendOrderCancelledEmail($orderInfo['email'], $orderInfo['full_name'], $order_id, (float)$orderInfo['total_amount']);
    }

    $_SESSION['success'] = 'Order status updated successfully';
    header('Location: orders.php');
    exit;
}

$page_title = 'Manage Orders';
$base_url = '../';
$hide_nav = true;
include '../js/includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div class="page-actions mb-3">
    <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<h1 class="mb-4">Manage Orders</h1>

<?php
// Get all orders
$stmt = $conn->prepare("SELECT o.*, u.username, u.full_name, u.email 
                        FROM orders o 
                        INNER JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC");
$stmt->execute();
$orders = $stmt->get_result();
?>

<table class="cart-table">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                    </td>
                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" class="table-select" onchange="this.form.submit()">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </form>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                    <td>
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View Details</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No orders found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../js/includes/footer.php'; ?>