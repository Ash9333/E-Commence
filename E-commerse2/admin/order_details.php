<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$conn = getDB();
cleanupOldCancelledOrders(10);

// Get order details
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                        FROM orders o 
                        INNER JOIN users u ON o.user_id = u.id 
                        WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Order not found';
    header('Location: orders.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
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

    $_SESSION['success'] = 'Order status updated';
    header('Location: order_details.php?id=' . $order_id);
    exit;
}

// Get order items
$stmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.image, 
                        u.username as seller_username, u.full_name as seller_name
                        FROM order_items oi 
                        INNER JOIN products p ON oi.product_id = p.id 
                        INNER JOIN users u ON oi.seller_id = u.id 
                        WHERE oi.order_id = ? 
                        ORDER BY oi.id");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result();

$page_title = 'Order Details #' . $order_id;
$base_url = '../';
$hide_nav = true;
include '../includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<div style="margin-bottom: 20px;">
    <a href="orders.php" class="btn btn-secondary">← Back to Orders</a>
</div>

<h1>Order Details #<?php echo $order['id']; ?></h1>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
    <div>
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h2 style="margin-bottom: 20px;">Order Information</h2>
            
            <form method="POST" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label>Update Status:</label>
                    <select name="status" style="width: 100%; padding: 10px;">
                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            </form>
            
            <div style="display: grid; gap: 15px;">
                <div>
                    <strong style="color: #666;">Order ID:</strong> #<?php echo $order['id']; ?>
                </div>
                <div>
                    <strong style="color: #666;">Order Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?>
                </div>
                <div>
                    <strong style="color: #666;">Current Status:</strong>
                    <span style="padding: 5px 10px; border-radius: 4px; background: #f0f0f0; font-weight: bold;">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                <div>
                    <strong style="color: #666;">Shipping Address:</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </div>
            </div>
        </div>
        
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 20px;">Order Items</h2>
            
            <?php if ($order_items->num_rows > 0): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Seller</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        while ($item = $order_items->fetch_assoc()): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                            <tr>
                                <td>
                                    <img src="../<?php echo $item['image'] ? htmlspecialchars($item['image']) : 'images/not_found.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px;">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['seller_name']); ?></strong><br>
                                    <small>@<?php echo htmlspecialchars($item['seller_username']); ?></small>
                                </td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo formatPrice($item_total); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div>
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 20px;">
            <h2>Order Summary</h2>
            <div style="display: grid; gap: 15px; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                    <span>Subtotal:</span>
                    <span><?php echo formatPrice($order['total_amount']); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                    <span>Shipping:</span>
                    <span>Free</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1.3rem; font-weight: bold; color: #ee4d2d; padding-top: 10px;">
                    <span>Total:</span>
                    <span><?php echo formatPrice($order['total_amount']); ?></span>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                <h3>Customer Information</h3>
                <div style="margin-top: 15px;">
                    <div style="margin-bottom: 10px;">
                        <strong>Name:</strong><br>
                        <?php echo htmlspecialchars($order['full_name']); ?>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong>Email:</strong><br>
                        <?php echo htmlspecialchars($order['email']); ?>
                    </div>
                    <?php if ($order['phone']): ?>
                        <div>
                            <strong>Phone:</strong><br>
                            <?php echo htmlspecialchars($order['phone']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>