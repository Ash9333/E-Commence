<?php
require_once 'config/database.php';
require_once 'js/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$conn = getDB();

// Get order details - verify it belongs to the user
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                        FROM orders o 
                        INNER JOIN users u ON o.user_id = u.id 
                        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Order not found';
    header('Location: dashboard.php');
    exit;
}

// Get order items with product and seller info
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

$page_title = t('order_details_title') . ' #' . $order_id;
include 'js/includes/header.php';

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<div style="margin-bottom: 20px;">
    <a href="dashboard.php" class="btn btn-secondary"><?php echo t('order_details_back'); ?></a>
</div>

<h1><?php echo t('order_details_title'); ?> #<?php echo $order['id']; ?></h1>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
    <div>
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h2 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ee4d2d;"><?php echo t('order_info_title'); ?></h2>
            
            <div style="display: grid; gap: 15px;">
                <div>
                    <strong style="color: #666; display: block; margin-bottom: 5px;"><?php echo t('order_info_order_id'); ?></strong>
                    <span style="font-size: 1.1rem;">#<?php echo $order['id']; ?></span>
                </div>
                
                <div>
                    <strong style="color: #666; display: block; margin-bottom: 5px;"><?php echo t('order_info_order_date'); ?></strong>
                    <span><?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                
                <div>
                    <strong style="color: #666; display: block; margin-bottom: 5px;"><?php echo t('order_info_status'); ?></strong>
                    <span style="padding: 8px 15px; border-radius: 4px; background: 
                        <?php 
                        switch($order['status']) {
                            case 'pending': echo '#fff3cd'; break;
                            case 'processing': echo '#cfe2ff'; break;
                            case 'shipped': echo '#d1ecf1'; break;
                            case 'delivered': echo '#d4edda'; break;
                            case 'cancelled': echo '#f8d7da'; break;
                            default: echo '#f0f0f0';
                        }
                        ?>; color: 
                        <?php 
                        switch($order['status']) {
                            case 'pending': echo '#856404'; break;
                            case 'processing': echo '#084298'; break;
                            case 'shipped': echo '#055160'; break;
                            case 'delivered': echo '#155724'; break;
                            case 'cancelled': echo '#721c24'; break;
                            default: echo '#333';
                        }
                        ?>; font-weight: bold;">
                        <?php echo t('order_status_' . $order['status']); ?>
                    </span>
                </div>
                
                <div>
                    <strong style="color: #666; display: block; margin-bottom: 5px;"><?php echo t('order_info_shipping_address'); ?></strong>
                    <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                </div>
            </div>
        </div>
        
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ee4d2d;"><?php echo t('order_items_title'); ?></h2>
            
            <?php if ($order_items->num_rows > 0): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th><?php echo t('order_items_product'); ?></th>
                            <th><?php echo t('order_items_seller'); ?></th>
                            <th><?php echo t('order_items_price'); ?></th>
                            <th><?php echo t('order_items_quantity'); ?></th>
                            <th><?php echo t('order_items_subtotal'); ?></th>
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
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?php echo $item['image'] ? htmlspecialchars($item['image']) : 'images/not_found.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"
                                             onerror="this.src='images/not_found.jpg'">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <br>
                                            <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                                               style="color: #ee4d2d; text-decoration: none; font-size: 0.9rem;">
                                                <?php echo t('order_items_view_product'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['seller_name']); ?></strong><br>
                                    <small style="color: #666;">@<?php echo htmlspecialchars($item['seller_username']); ?></small>
                                </td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo formatPrice($item_total); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo t('order_items_empty'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div>
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 20px;">
            <h2 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ee4d2d;"><?php echo t('order_summary_title'); ?></h2>
            
            <div style="display: grid; gap: 15px;">
                <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                    <span style="color: #666;"><?php echo t('order_summary_subtotal'); ?></span>
                    <span><?php echo formatPrice($subtotal ?? $order['total_amount']); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                    <span style="color: #666;"><?php echo t('order_summary_shipping'); ?></span>
                    <span><?php echo t('order_summary_shipping_free'); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; padding-top: 10px; font-size: 1.3rem; font-weight: bold; color: #ee4d2d;">
                    <span><?php echo t('order_summary_total'); ?></span>
                    <span><?php echo formatPrice($order['total_amount']); ?></span>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                <h3 style="margin-bottom: 15px; font-size: 1.1rem;"><?php echo t('order_customer_info_title'); ?></h3>
                <div style="display: grid; gap: 10px; font-size: 0.95rem;">
                    <div>
                        <strong style="color: #666;"><?php echo t('order_customer_name'); ?></strong><br>
                        <?php echo htmlspecialchars($order['full_name']); ?>
                    </div>
                    <div>
                        <strong style="color: #666;"><?php echo t('order_customer_email'); ?></strong><br>
                        <?php echo htmlspecialchars($order['email']); ?>
                    </div>
                    <?php if ($order['phone']): ?>
                        <div>
                            <strong style="color: #666;"><?php echo t('order_customer_phone'); ?></strong><br>
                            <?php echo htmlspecialchars($order['phone']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'js/includes/footer.php'; ?>