<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

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
include $base_url . 'includes/header.php';
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>
    <button type="button" id="theme-toggle" class="theme-toggle">
        Night
    </button>
</div>

<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?php echo $stats['products']; ?></div>
        <div style="font-size: 1rem; opacity: 0.9;">Total Products</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?php echo $stats['orders']; ?></div>
        <div style="font-size: 1rem; opacity: 0.9;">Total Orders</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?php echo $stats['users']; ?></div>
        <div style="font-size: 1rem; opacity: 0.9;">Total Users</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">$<?php echo number_format($stats['platform_revenue'], 2); ?></div>
        <div style="font-size: 1rem; opacity: 0.9;">Platform Revenue (10% of delivered)</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?php echo $stats['pending_orders']; ?></div>
        <div style="font-size: 1rem; opacity: 0.9;">Pending Orders</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?php echo $stats['sellers']; ?></div>
        <div style="font-size: 1rem; opacity: 0.9;">Total Sellers</div>
    </div>
</div>

<!-- Quick Actions -->
<h2 style="margin-bottom: 20px;">Quick Actions</h2>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
    <a href="products.php" class="btn btn-primary" style="padding: 30px; text-align: center; font-size: 1.2rem; text-decoration: none; display: block;">
        📦 Manage Products
    </a>
    <a href="orders.php" class="btn btn-secondary" style="padding: 30px; text-align: center; font-size: 1.2rem; text-decoration: none; display: block;">
        🛒 Manage Orders
    </a>
    <a href="users.php" class="btn btn-secondary" style="padding: 30px; text-align: center; font-size: 1.2rem; text-decoration: none; display: block;">
        👥 Manage Users
    </a>
    <a href="categories.php" class="btn btn-secondary" style="padding: 30px; text-align: center; font-size: 1.2rem; text-decoration: none; display: block;">
        📁 Manage Categories
    </a>
    <a href="../index.php" class="btn btn-secondary" style="padding: 30px; text-align: center; font-size: 1.2rem; text-decoration: none; display: block;">
        🏠 View Store
    </a>
    <a href="../logout.php" class="btn btn-secondary" style="padding: 30px; text-align: center; font-size: 1.2rem; text-decoration: none; display: block; background: #dc3545;">
        🚪 Logout
    </a>
</div>

<?php include '../includes/footer.php'; ?>