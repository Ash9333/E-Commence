<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$conn = getDB();

// Get statistics
$stats = [];

// Total products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$stats['products'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['orders'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['users'] = $result->fetch_assoc()['count'];

// Total revenue from delivered orders and platform share (10%)
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'");
$stats['gross_revenue']     = $result->fetch_assoc()['total'] ?? 0;
$stats['platform_revenue']  = $stats['gross_revenue'] * 0.10; // 10% platform fee

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Total sellers
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'seller'");
$stats['sellers'] = $result->fetch_assoc()['count'];

$base_url = '../';
$page_title = 'Admin Dashboard';
$hide_nav = true;
include $base_url . 'js/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>
    <button
        type="button"
        id="theme-toggle"
        class="theme-toggle"
        data-label-night="<?php echo htmlspecialchars(t('theme_night'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
        data-label-light="<?php echo htmlspecialchars(t('theme_light'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
        <i class="bi bi-moon-fill theme-toggle-icon" aria-hidden="true"></i>
        <span class="theme-toggle-text"><?php echo t('theme_night'); ?></span>
    </button>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
            <i class="bi bi-box-seam stats-icon" aria-hidden="true"></i>
            <?php echo $stats['products']; ?>
        </div>
        <div style="font-size: 1rem; opacity: 0.9;">Total Products</div>
    </div>
    
    <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
            <i class="bi bi-cart-check stats-icon" aria-hidden="true"></i>
            <?php echo $stats['orders']; ?>
        </div>
        <div style="font-size: 1rem; opacity: 0.9;">Total Orders</div>
    </div>
    
    <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
            <i class="bi bi-people-fill stats-icon" aria-hidden="true"></i>
            <?php echo $stats['users']; ?>
        </div>
        <div style="font-size: 1rem; opacity: 0.9;">Total Users</div>
    </div>
    
    <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
            <i class="bi bi-cash-coin stats-icon" aria-hidden="true"></i>
            $<?php echo number_format($stats['platform_revenue'], 2); ?>
        </div>
        <div style="font-size: 1rem; opacity: 0.9;">Platform Revenue (10% of delivered)</div>
    </div>
    
    <div class="stats-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
            <i class="bi bi-hourglass-split stats-icon" aria-hidden="true"></i>
            <?php echo $stats['pending_orders']; ?>
        </div>
        <div style="font-size: 1rem; opacity: 0.9;">Pending Orders</div>
    </div>
    
    <div class="stats-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white;">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
            <i class="bi bi-shop stats-icon" aria-hidden="true"></i>
            <?php echo $stats['sellers']; ?>
        </div>
        <div style="font-size: 1rem; opacity: 0.9;">Total Sellers</div>
    </div>
</div>

<!-- Quick Actions -->
<h2 class="section-title">Quick Actions</h2>
<div class="quick-actions-grid">
    <a href="products.php" class="btn btn-primary quick-action-link">
        <i class="bi bi-box-seam quick-action-icon" aria-hidden="true"></i>
        Manage Products
    </a>
    <a href="orders.php" class="btn btn-secondary quick-action-link">
        <i class="bi bi-cart-check quick-action-icon" aria-hidden="true"></i>
        Manage Orders
    </a>
    <a href="users.php" class="btn btn-secondary quick-action-link">
        <i class="bi bi-people-fill quick-action-icon" aria-hidden="true"></i>
        Manage Users
    </a>
    <a href="categories.php" class="btn btn-secondary quick-action-link">
        <i class="bi bi-folder2-open quick-action-icon" aria-hidden="true"></i>
        Manage Categories
    </a>
    <a href="../index.php" class="btn btn-secondary quick-action-link">
        <i class="bi bi-house-door quick-action-icon" aria-hidden="true"></i>
        View Store
    </a>
    <a href="../logout.php" class="btn btn-danger quick-action-link">
        <i class="bi bi-box-arrow-right quick-action-icon" aria-hidden="true"></i>
        Logout
    </a>
</div>

<?php include '../js/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>