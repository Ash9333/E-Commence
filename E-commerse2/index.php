<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : null;
$products = getProducts($category_id, $search);
$categories = getCategories();

$page_title = 'Shop - Online Shopping';
include 'includes/header.php';
?>

<div class="categories">
    <a href="index.php" class="category-btn <?php echo !$category_id ? 'active' : ''; ?>">All</a>
    <?php while ($category = $categories->fetch_assoc()): ?>
        <a href="index.php?category=<?php echo $category['id']; ?>" 
           class="category-btn <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($category['name']); ?>
        </a>
    <?php endwhile; ?>
</div>

<div class="products-grid">
    <?php while ($product = $products->fetch_assoc()): ?>
        <div class="product-card">
            <img src="<?php echo $product['image'] ? htmlspecialchars($product['image']) : 'images/not_found.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                 class="product-image"
                 onerror="this.src='images/not_found.jpg'">
            <div class="product-info">
                <h3 class="product-name">
                    <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </a>
                </h3>
                <div class="product-price">
                    <?php if ($product['min_price'] == $product['max_price']): ?>
                        <?php echo formatPrice($product['min_price']); ?>
                    <?php else: ?>
                        <?php echo formatPrice($product['min_price']); ?> - <?php echo formatPrice($product['max_price']); ?>
                    <?php endif; ?>
                </div>
                <div class="product-stock">Stock: <?php echo $product['stock']; ?></div>
                <?php if (isLoggedIn()): ?>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Login to Buy</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>