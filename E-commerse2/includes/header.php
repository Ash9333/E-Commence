<?php
require_once __DIR__ . '/functions.php';
$cart_count = isLoggedIn() ? getUserCartCount($_SESSION['user_id']) : 0;
$base_url = isset($base_url) ? $base_url : '';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : t('brand_name') . ' - Online Shopping'; ?></title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css">
</head>
<body>
    <?php if (empty($hide_nav)): ?>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="<?php echo $base_url; ?>index.php">🛍️ <?php echo t('brand_name'); ?></a>
            </div>

            <div class="nav-search">
                <form action="<?php echo $base_url; ?>index.php" method="GET">
                    <input type="text" name="search" placeholder="<?php echo t('search_placeholder'); ?>" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit"><?php echo t('search_button'); ?></button>
                </form>
            </div>
            <div class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo $base_url; ?>admin/index.php"><?php echo t('nav_admin'); ?></a>
                    <?php endif; ?>
                    <?php if (isSeller()): ?>
                        <a href="<?php echo $base_url; ?>seller/index.php"><?php echo t('nav_seller'); ?></a>
                    <?php endif; ?>
                    <a href="<?php echo $base_url; ?>cart.php"><?php echo sprintf(t('nav_cart_with_count'), $cart_count); ?></a>
                    <a href="<?php echo $base_url; ?>dashboard.php"><?php echo t('nav_dashboard'); ?></a>
                    <a href="<?php echo $base_url; ?>profile.php"><?php echo t('nav_profile'); ?></a>
                    <a href="<?php echo $base_url; ?>logout.php"><?php echo t('nav_logout'); ?> (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>login.php"><?php echo t('nav_login'); ?></a>
                    <a href="<?php echo $base_url; ?>register.php"><?php echo t('nav_register'); ?></a>
                <?php endif; ?>
                <button
                    type="button"
                    id="theme-toggle"
                    class="theme-toggle"
                    data-label-night="<?php echo htmlspecialchars(t('theme_night'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                    data-label-light="<?php echo htmlspecialchars(t('theme_light'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    🌙 <?php echo t('theme_night'); ?>
                </button>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main class="container">