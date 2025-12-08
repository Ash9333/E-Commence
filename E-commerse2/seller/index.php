<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';

if (!isLoggedIn() || !isSeller()) {
    header('Location: ../login.php');
    exit;
}

$conn      = getDB();
$seller_id = $_SESSION['user_id'];

// Basic seller stats
// Total products this seller offers
$stmt = $conn->prepare("SELECT COUNT(DISTINCT product_id) AS cnt FROM product_sellers WHERE seller_id = ?");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$stats_products = ($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);

// Total orders that include this seller's items
$stmt = $conn->prepare("SELECT COUNT(DISTINCT oi.order_id) AS cnt
                        FROM order_items oi
                        WHERE oi.seller_id = ?");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$stats_orders = ($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);

// Gross revenue for this seller (sum of item price * quantity)
$stmt = $conn->prepare("SELECT SUM(oi.price * oi.quantity) AS total
                        FROM order_items oi
                        WHERE oi.seller_id = ?");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$stats_gross_revenue = ($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// Platform fee: 10% of gross revenue, net revenue is what seller effectively earns
$platform_fee_rate  = 0.10;
$stats_platform_fee = $stats_gross_revenue * $platform_fee_rate;
$stats_net_revenue  = $stats_gross_revenue - $stats_platform_fee;

$page_title = t('seller_dashboard_title');
$base_url   = '../';
include '../js/includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<h1><?php echo t('seller_dashboard_title'); ?></h1>
<p><?php echo sprintf(t('seller_dashboard_welcome'), htmlspecialchars($_SESSION['username'])); ?></p>

<!-- Seller Stats -->
<div class="stats-grid stats-grid-centered">
    <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;">
            <i class="bi bi-box-seam stats-icon" aria-hidden="true"></i>
            <?php echo (int)$stats_products; ?>
        </div>
        <div><?php echo t('seller_dashboard_total_products'); ?></div>
    </div>
    <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;">
            <i class="bi bi-cart-check stats-icon" aria-hidden="true"></i>
            <?php echo (int)$stats_orders; ?>
        </div>
        <div><?php echo t('seller_dashboard_total_orders'); ?></div>
    </div>
    <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;">
            <i class="bi bi-cash-coin stats-icon" aria-hidden="true"></i>
            <?php echo formatPrice($stats_gross_revenue ?? 0); ?>
        </div>
        <div><?php echo t('seller_dashboard_gross_revenue'); ?></div>
    </div>
    <div class="stats-card" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); color: white;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;">
            <i class="bi bi-receipt-cutoff stats-icon" aria-hidden="true"></i>
            <?php echo formatPrice($stats_platform_fee ?? 0); ?>
        </div>
        <div><?php echo t('seller_dashboard_platform_fees'); ?></div>
    </div>
    <div class="stats-card" style="background: linear-gradient(135deg, #16a085 0%, #27ae60 100%); color: white;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;">
            <i class="bi bi-wallet2 stats-icon" aria-hidden="true"></i>
            <?php echo formatPrice($stats_net_revenue ?? 0); ?>
        </div>
        <div><?php echo t('seller_dashboard_net_revenue'); ?></div>
    </div>
</div>

<!-- Seller Quick Actions -->
<h2 class="section-title"><?php echo t('seller_dashboard_actions'); ?></h2>
<div class="quick-actions-grid">
    <a href="products.php" class="btn btn-primary quick-action-link">
        <i class="bi bi-box-seam quick-action-icon" aria-hidden="true"></i>
        <?php echo t('seller_dashboard_my_products'); ?>
    </a>
    <a href="orders.php" class="btn btn-secondary quick-action-link">
        <i class="bi bi-cart-check quick-action-icon" aria-hidden="true"></i>
        <?php echo t('seller_dashboard_my_orders'); ?>
    </a>
    <a href="../index.php" class="btn btn-secondary quick-action-link">
        <i class="bi bi-house-door quick-action-icon" aria-hidden="true"></i>
        <?php echo t('seller_dashboard_back_to_store'); ?>
    </a>
</div>

<?php include '../js/includes/footer.php'; ?>
