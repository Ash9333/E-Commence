<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

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

$page_title = 'Seller Dashboard';
$base_url   = '../';
include '../includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<h1>Seller Dashboard</h1>
<p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>. Here is an overview of your sales activity.</p>

<!-- Seller Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;"><?php echo (int)$stats_products; ?></div>
        <div>Total Products</div>
    </div>
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;"><?php echo (int)$stats_orders; ?></div>
        <div>Total Orders</div>
    </div>
    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;"><?php echo formatPrice($stats_gross_revenue ?? 0); ?></div>
        <div>Gross Revenue</div>
    </div>
    <div style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;"><?php echo formatPrice($stats_platform_fee ?? 0); ?></div>
        <div>Platform Fees (10%)</div>
    </div>
    <div style="background: linear-gradient(135deg, #16a085 0%, #27ae60 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 8px;"><?php echo formatPrice($stats_net_revenue ?? 0); ?></div>
        <div>Net Revenue (after fees)</div>
    </div>
</div>

<!-- Seller Quick Actions -->
<h2 style="margin-bottom: 20px;">Seller Actions</h2>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
    <a href="products.php" class="btn btn-primary" style="padding: 20px; text-align: center; font-size: 1.1rem; text-decoration: none; display: block;">📦 My Products</a>
    <a href="orders.php" class="btn btn-secondary" style="padding: 20px; text-align: center; font-size: 1.1rem; text-decoration: none; display: block;">🛒 My Orders</a>
    <a href="../index.php" class="btn btn-secondary" style="padding: 20px; text-align: center; font-size: 1.1rem; text-decoration: none; display: block;">🏠 Back to Store</a>
</div>

<?php include '../includes/footer.php'; ?>
