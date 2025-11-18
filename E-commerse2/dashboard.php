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

$page_title = t('dashboard_title');

include 'includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<h1><?php echo t('dashboard_title'); ?></h1>

<div style="display: grid; grid-template-columns: 1fr 3fr; gap: 20px;">
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h2><?php echo t('dashboard_menu'); ?></h2>
        <a href="dashboard.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; display: block; text-align: center;"><?php echo t('dashboard_my_orders'); ?></a>
        <a href="profile.php" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px; display: block; text-align: center;"><?php echo t('dashboard_profile_settings'); ?></a>
        <a href="index.php" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px; display: block; text-align: center;"><?php echo t('dashboard_continue_shopping'); ?></a>
        <a href="cart.php" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px; display: block; text-align: center;"><?php echo t('dashboard_cart'); ?></a>
        <?php if (!isSeller()): ?>
            <a href="become_seller.php" class="btn btn-secondary" style="width: 100%; display: block; text-align: center; background: #ff9800; margin-top: 10px;"><?php echo t('dashboard_become_seller'); ?></a>
        <?php endif; ?>
    </div>
    
    <div>
        <h2><?php echo t('dashboard_my_orders'); ?></h2>
        <?php if ($orders->num_rows > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th><?php echo t('orders_table_order_id'); ?></th>
                        <th><?php echo t('orders_table_total'); ?></th>
                        <th><?php echo t('orders_table_status'); ?></th>
                        <th><?php echo t('orders_table_date'); ?></th>
                        <th><?php echo t('orders_table_action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td>
                                <span style="padding: 5px 10px; border-radius: 4px; background: #f0f0f0;">
                                    <?php echo t('order_status_' . $order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary"><?php echo t('orders_view_details'); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php echo t('orders_empty_message'); ?> <a href="index.php"><?php echo t('orders_start_shopping'); ?></a></p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>