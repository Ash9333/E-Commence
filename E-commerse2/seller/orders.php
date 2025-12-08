<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';
require_once '../js/includes/mailer.php';

if (!isLoggedIn() || !isSeller()) {
    header('Location: ../login.php');
    exit;
}

$conn      = getDB();
cleanupOldCancelledOrders(10);
$seller_id = $_SESSION['user_id'];

// Allow seller to update order status, but only for orders that contain their items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status   = sanitize($_POST['status']);

    // Verify this order contains items sold by this seller
    $stmt = $conn->prepare("SELECT 1
                            FROM order_items
                            WHERE order_id = ? AND seller_id = ?
                            LIMIT 1");
    $stmt->bind_param('ii', $order_id, $seller_id);
    $stmt->execute();
    $owns = $stmt->get_result()->fetch_assoc();

    if ($owns) {
        $stmt = $conn->prepare("SELECT o.status, o.total_amount, u.full_name, u.email FROM orders o INNER JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->bind_param('i', $order_id);
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
        $stmt->bind_param('si', $status, $order_id);
        $stmt->execute();

        if ($sendCancelEmail) {
            sendOrderCancelledEmail($orderInfo['email'], $orderInfo['full_name'], $order_id, (float)$orderInfo['total_amount']);
        }

        $_SESSION['success'] = 'Order status updated successfully.';
    }

    header('Location: orders.php');
    exit;
}

// Get orders that contain this seller's items
$stmt = $conn->prepare("SELECT DISTINCT o.*, u.full_name, u.email
                        FROM orders o
                        INNER JOIN order_items oi ON oi.order_id = o.id
                        INNER JOIN users u ON o.user_id = u.id
                        WHERE oi.seller_id = ?
                        ORDER BY o.created_at DESC");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$orders = $stmt->get_result();

$page_title = t('seller_orders_title');
$base_url   = '../';
include '../js/includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<h1><?php echo t('seller_orders_title'); ?></h1>
<p><?php echo t('seller_orders_intro'); ?></p>

<?php if ($orders->num_rows > 0): ?>
    <table class="cart-table">
        <thead>
            <tr>
                <th><?php echo t('seller_orders_order_id'); ?></th>
                <th><?php echo t('seller_orders_customer'); ?></th>
                <th><?php echo t('seller_orders_total_amount'); ?></th>
                <th><?php echo t('seller_orders_status'); ?></th>
                <th><?php echo t('seller_orders_date'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                        <small style="color: #666;"><?php echo htmlspecialchars($order['email']); ?></small>
                    </td>
                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" onchange="this.form.submit()" 
                                    style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>><?php echo t('order_status_pending'); ?></option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>><?php echo t('order_status_processing'); ?></option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>><?php echo t('order_status_shipped'); ?></option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>><?php echo t('order_status_delivered'); ?></option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>><?php echo t('order_status_cancelled'); ?></option>
                            </select>
                        </form>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p><?php echo t('seller_orders_no_orders'); ?></p>
<?php endif; ?>

<div style="margin-top: 20px;">
    <a href="index.php" class="btn btn-secondary"><?php echo t('seller_orders_back_to_dashboard'); ?></a>
</div>

<?php include '../js/includes/footer.php'; ?>
