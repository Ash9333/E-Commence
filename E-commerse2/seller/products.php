<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';

if (!isLoggedIn() || !isSeller()) {
    header('Location: ../login.php');
    exit;
}

// DB connection and current seller
$conn      = getDB();
$seller_id = $_SESSION['user_id'];

// Handle create / update / delete from seller
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'add') {
        // create new product owned by this seller
        $name        = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price       = (float)$_POST['price'];
        $stock       = (int)$_POST['stock'];
        $image_path  = null;

        // simple image upload (similar to admin/products.php)
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
                    $image_path = 'uploads/images/' . $file_name;
                }
            }
        }

        if ($image_path) {
            $stmt = $conn->prepare(
                "INSERT INTO products (name, description, price, stock, image)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssdis", $name, $description, $price, $stock, $image_path);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO products (name, description, price, stock)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssdi", $name, $description, $price, $stock);
        }
        $stmt->execute();
        $product_id = $conn->insert_id;

        // attach this product to the current seller
        $stmt = $conn->prepare(
            "INSERT INTO product_sellers (product_id, seller_id, seller_price, seller_stock)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("iidi", $product_id, $seller_id, $price, $stock);
        $stmt->execute();

        $_SESSION['success'] = t('flash_product_created');
        header('Location: products.php');
        exit;
    }

    if ($form_type === 'update') {
        // update price/stock for this seller on an existing product
        $product_id   = (int)$_POST['product_id'];
        $seller_price = (float)$_POST['seller_price'];
        $seller_stock = (int)$_POST['seller_stock'];

        $stmt = $conn->prepare(
            "UPDATE product_sellers
             SET seller_price = ?, seller_stock = ?
             WHERE product_id = ? AND seller_id = ?"
        );
        $stmt->bind_param("diii", $seller_price, $seller_stock, $product_id, $seller_id);
        $stmt->execute();

        $_SESSION['success'] = t('flash_product_updated');
        header('Location: products.php');
        exit;
    } elseif ($form_type === 'delete') {
        $product_id = (int)$_POST['product_id'];

        // Remove this seller's association with the product
        $stmt = $conn->prepare(
            "DELETE FROM product_sellers WHERE product_id = ? AND seller_id = ?"
        );
        $stmt->bind_param("ii", $product_id, $seller_id);
        $stmt->execute();

        // Check if any sellers remain for this product
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM product_sellers WHERE product_id = ?"
        );
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $count_row = $stmt->get_result()->fetch_assoc();

        if (($count_row['cnt'] ?? 0) == 0) {
            // No sellers left; optionally delete product and image
            $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $product_row = $stmt->get_result()->fetch_assoc();

            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            if ($product_row && $product_row['image'] && file_exists('../' . $product_row['image'])) {
                @unlink('../' . $product_row['image']);
            }
        }

        $_SESSION['success'] = t('flash_product_stopped_selling');
        header('Location: products.php');
        exit;
    }
}

// Get products for this seller
$stmt = $conn->prepare("SELECT p.*, ps.seller_price, ps.seller_stock
                        FROM product_sellers ps
                        INNER JOIN products p ON ps.product_id = p.id
                        WHERE ps.seller_id = ?
                        ORDER BY p.created_at DESC");
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$products = $stmt->get_result();

$page_title = t('seller_products_title');
$base_url   = '../';
include '../js/includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<h1><?php echo t('seller_products_title'); ?></h1>
<p><?php echo t('seller_products_intro'); ?></p>

<div style="margin: 20px 0; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <h2 style="margin-bottom: 15px;"><?php echo t('seller_products_add_new'); ?></h2>
    <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 10px; max-width: 500px;">
        <input type="hidden" name="form_type" value="add">
        <div class="form-group">
            <label><?php echo t('seller_products_name'); ?>:</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_products_description'); ?>:</label>
            <textarea name="description" required></textarea>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_products_price'); ?>:</label>
            <input type="number" name="price" step="0.01" min="0" required>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_products_stock'); ?>:</label>
            <input type="number" name="stock" min="0" required>
        </div>
        <div class="form-group">
            <label><?php echo t('seller_products_image'); ?>:</label>
            <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        </div>
        <button type="submit" class="btn btn-primary"><?php echo t('seller_products_create_button'); ?></button>
    </form>
</div>

<?php if ($products->num_rows > 0): ?>
    <h2 style="margin-top: 30px;"><?php echo t('seller_products_your_products'); ?></h2>
    <table class="cart-table">
        <thead>
            <tr>
                <th><?php echo t('seller_products_image_column'); ?></th>
                <th><?php echo t('seller_products_name_column'); ?></th>
                <th><?php echo t('seller_products_price_stock_column'); ?></th>
                <th><?php echo t('seller_products_created_column'); ?></th>
                <th><?php echo t('seller_products_actions_column'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = $products->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($product['image']): ?>
                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">
                                <?php echo t('seller_products_no_image'); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td>
                        <form method="POST" style="display: flex; gap: 6px; align-items: center;">
                            <input type="hidden" name="form_type" value="update">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="number" name="seller_price" step="0.01" min="0" 
                                   value="<?php echo htmlspecialchars($product['seller_price']); ?>" 
                                   style="width: 90px; padding: 3px;">
                            <input type="number" name="seller_stock" min="0" 
                                   value="<?php echo (int)$product['seller_stock']; ?>" 
                                   style="width: 80px; padding: 3px;">
                            <button type="submit" class="btn btn-secondary" style="margin-right: 5px;">
                                <?php echo t('seller_products_save'); ?>
                            </button>
                        </form>
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary" style="margin-right: 5px;">
                            <?php echo t('seller_products_edit_details'); ?>
                        </a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo t('confirm_stop_selling_product'); ?>');">
                            <input type="hidden" name="form_type" value="delete">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-secondary" style="background: #dc3545;">
                                <?php echo t('seller_products_stop_selling'); ?>
                            </button>
                        </form>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                    <td>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p><?php echo t('seller_products_not_selling'); ?></p>
<?php endif; ?>

<div style="margin-top: 20px;">
    <a href="index.php" class="btn btn-secondary">
        <?php echo t('seller_products_back_to_dashboard'); ?>
    </a>
</div>

<?php include '../js/includes/footer.php'; ?>
