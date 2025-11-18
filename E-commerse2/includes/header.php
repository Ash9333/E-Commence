<?php
require_once __DIR__ . '/functions.php';
$cart_count = isLoggedIn() ? getUserCartCount($_SESSION['user_id']) : 0;
$base_url = isset($base_url) ? $base_url : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Shop - Online Shopping'; ?></title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css">
</head>
<body>
    <?php if (empty($hide_nav)): ?>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="<?php echo $base_url; ?>index.php">🛍️ Shop</a>
            </div>

            <div class="nav-search">
                <form action="<?php echo $base_url; ?>index.php" method="GET">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo $base_url; ?>admin/index.php">Admin</a>
                    <?php endif; ?>
                    <?php if (isSeller()): ?>
                        <a href="<?php echo $base_url; ?>seller/index.php">Seller</a>
                    <?php endif; ?>
                    <a href="<?php echo $base_url; ?>cart.php">Cart (<?php echo $cart_count; ?>)</a>
                    <a href="<?php echo $base_url; ?>dashboard.php">Dashboard</a>
                    <a href="<?php echo $base_url; ?>profile.php">Profile</a>
                    <a href="<?php echo $base_url; ?>logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>login.php">Login</a>
                    <a href="<?php echo $base_url; ?>register.php">Register</a>
                <?php endif; ?>
                <button type="button" id="theme-toggle" class="theme-toggle">🌙 Night</button>
            </div>
        </div>
    </nav>

    <?php endif; ?>
    <main class="container">