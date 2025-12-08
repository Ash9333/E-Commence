<?php
require_once 'config/database.php';
require_once 'js/includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = (int)$_GET['id'];
$conn = getDB();
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: index.php');
    exit;
}

// Get sellers for this product (many-to-many)
$product_sellers = getProductSellers($product_id);

// Get categories for this product
$stmt = $conn->prepare("SELECT c.name FROM categories c 
                        INNER JOIN product_categories pc ON c.id = pc.category_id 
                        WHERE pc.product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product_categories = $stmt->get_result();

$page_title = htmlspecialchars($product['name']);
include 'js/includes/header.php';
?>

<div class="product-detail">
    <div>
        <img src="<?php echo $product['image'] ? htmlspecialchars($product['image']) : 'images/not_found.jpg'; ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>" 
             class="product-detail-image"
             onerror="this.src='images/not_found.jpg'">
    </div>
    <div class="product-detail-info">
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <div class="categories" style="margin: 15px 0;">
            <?php while ($cat = $product_categories->fetch_assoc()): ?>
                <span class="category-btn"><?php echo htmlspecialchars(translateCategoryLabel($cat['name'])); ?></span>
            <?php endwhile; ?>
        </div>
        
        <div class="product-description">
            <h3><?php echo t('product_description_title'); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>
        
        <h3 style="margin-top: 30px; margin-bottom: 15px;"><?php echo t('product_available_from_sellers'); ?></h3>
        
        <?php if ($product_sellers->num_rows > 0): ?>
            <div style="display: grid; gap: 15px;">
                <?php while ($seller_info = $product_sellers->fetch_assoc()): ?>
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <strong><?php echo htmlspecialchars($seller_info['full_name']); ?></strong>
                                <p style="margin: 5px 0; color: #666;">@<?php echo htmlspecialchars($seller_info['username']); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.3rem; font-weight: bold; color: #ee4d2d;">
                                    <?php echo formatPrice($seller_info['seller_price'] ?? $product['price']); ?>
                                </div>
                                <div style="color: #666; font-size: 0.9rem;">
                                    <?php echo t('product_stock_label'); ?>: <?php echo $seller_info['seller_stock'] ?? $seller_info['stock'] ?? 0; ?>
                                </div>
                            </div>
                        </div>
                        <?php if (isLoggedIn() && ($seller_info['seller_stock'] ?? 0) > 0): ?>
                            <form action="api/add_to_cart.php" method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="seller_id" value="<?php echo $seller_info['seller_id']; ?>">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <label><?php echo t('product_quantity_label'); ?></label>
                                    <input type="number" name="quantity" value="1" 
                                           min="1" max="<?php echo $seller_info['seller_stock'] ?? 0; ?>" 
                                           style="width: 80px; padding: 5px;">
                                    <button type="submit" class="btn btn-primary"><?php echo t('product_add_to_cart_button'); ?></button>
                                </div>
                            </form>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="login.php" class="btn btn-secondary" style="margin-top: 10px;"><?php echo t('home_login_to_buy'); ?></a>
                        <?php else: ?>
                            <p style="color: red; margin-top: 10px;"><?php echo t('product_out_of_stock'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: #666; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                <?php echo t('product_no_sellers_message'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'js/includes/footer.php'; ?>