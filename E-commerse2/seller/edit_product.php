<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';

if (!isLoggedIn() || !isSeller() || !isset($_GET['id'])) {
    header('Location: ../login.php');
    exit;
}

$conn      = getDB();
$seller_id = $_SESSION['user_id'];
$product_id = (int)$_GET['id'];

// Verify this product belongs to this seller (via product_sellers)
$stmt = $conn->prepare("SELECT p.*, ps.seller_price, ps.seller_stock
                        FROM products p
                        INNER JOIN product_sellers ps ON ps.product_id = p.id
                        WHERE p.id = ? AND ps.seller_id = ?");
$stmt->bind_param('ii', $product_id, $seller_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    $_SESSION['error'] = t('error_seller_product_not_found');
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $base_price  = (float)$_POST['base_price'];
    $base_stock  = (int)$_POST['base_stock'];
    $seller_price = (float)$_POST['seller_price'];
    $seller_stock = (int)$_POST['seller_stock'];

    $image_path = $product['image'];

    // Optional new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $file_name   = uniqid() . '_' . time() . '.' . $ext;
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                // delete old image
                if ($image_path && file_exists('../' . $image_path)) {
                    @unlink('../' . $image_path);
                }
                $image_path = 'uploads/images/' . $file_name;
            }
        }
    }

    if ($image_path) {
        $stmt = $conn->prepare("UPDATE products
                                SET name = ?, description = ?, price = ?, stock = ?, image = ?
                                WHERE id = ?");
        $stmt->bind_param('ssdisi', $name, $description, $base_price, $base_stock, $image_path, $product_id);
    } else {
        $stmt = $conn->prepare("UPDATE products
                                SET name = ?, description = ?, price = ?, stock = ?
                                WHERE id = ?");
        $stmt->bind_param('ssdii', $name, $description, $base_price, $base_stock, $product_id);
    }
    $stmt->execute();

    // Update this seller's price/stock
    $stmt = $conn->prepare("UPDATE product_sellers
                            SET seller_price = ?, seller_stock = ?
                            WHERE product_id = ? AND seller_id = ?");
    $stmt->bind_param('diii', $seller_price, $seller_stock, $product_id, $seller_id);
    $stmt->execute();

    $_SESSION['success'] = t('flash_product_updated');
    header('Location: products.php');
    exit;
}

$page_title = t('seller_edit_title');
$base_url   = '../';
include '../js/includes/header.php';

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>'; 
    unset($_SESSION['error']);
}
?>

<h1><?php echo t('seller_edit_title'); ?></h1>
<p><?php echo t('seller_edit_intro'); ?></p>

<div class="form-container" style="max-width: 600px;">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label><?php echo t('seller_products_name'); ?>:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_products_description'); ?>:</label>
            <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_edit_base_price_label'); ?></label>
            <input type="number" name="base_price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_edit_base_stock_label'); ?></label>
            <input type="number" name="base_stock" min="0" value="<?php echo $product['stock']; ?>" required>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_edit_seller_price_label'); ?></label>
            <input type="number" name="seller_price" step="0.01" min="0" value="<?php echo $product['seller_price']; ?>" required>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_edit_seller_stock_label'); ?></label>
            <input type="number" name="seller_stock" min="0" value="<?php echo $product['seller_stock']; ?>" required>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_products_image'); ?>:</label>
            <?php if (!empty($product['image'])): ?>
                <div style="margin-bottom: 10px;">
                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="Current image" 
                         style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small style="color:#666;"><?php echo t('seller_edit_image_hint'); ?></small>
        </div>

        <button type="submit" class="btn btn-primary"><?php echo t('seller_edit_save_changes'); ?></button>
        <a href="products.php" class="btn btn-secondary"><?php echo t('button_cancel'); ?></a>
    </form>
</div>

<?php include '../js/includes/footer.php'; ?>
