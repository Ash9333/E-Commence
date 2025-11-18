<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$conn = getDB();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $category_ids = isset($_POST['categories']) ? $_POST['categories'] : [];
        $seller_ids = isset($_POST['sellers']) ? $_POST['sellers'] : [];
        
        // Handle file upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/images/' . $file_name;
                    
                    // Delete old image if editing
                    if ($action === 'edit' && $product_id) {
                        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $old_product = $stmt->get_result()->fetch_assoc();
                        if ($old_product && $old_product['image'] && file_exists('../' . $old_product['image'])) {
                            unlink('../' . $old_product['image']);
                        }
                    }
                } else {
                    $_SESSION['error'] = t('flash_image_upload_failed');
                }
            } else {
                $_SESSION['error'] = t('flash_invalid_file_type');
            }
        }
        
        if (!isset($_SESSION['error'])) {
            if ($action === 'add') {
                if ($image_path) {
                    $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssdis", $name, $description, $price, $stock, $image_path);
                } else {
                    $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssdi", $name, $description, $price, $stock);
                }
                $stmt->execute();
                $product_id = $conn->insert_id;
                $_SESSION['success'] = t('flash_product_added');
            } else if ($action === 'edit' && $product_id) {
                if ($image_path) {
                    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
                    $stmt->bind_param("ssdisi", $name, $description, $price, $stock, $image_path, $product_id);
                } else {
                    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ? WHERE id = ?");
                    $stmt->bind_param("ssdii", $name, $description, $price, $stock, $product_id);
                }
                $stmt->execute();
                $_SESSION['success'] = t('flash_product_updated');
            }
            
            // Handle sellers (many-to-many)
            if ($product_id) {
                // Remove existing sellers
                $stmt = $conn->prepare("DELETE FROM product_sellers WHERE product_id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                
                // Add selected sellers
                if (!empty($seller_ids)) {
                    foreach ($seller_ids as $seller_id) {
                        $seller_id = (int)$seller_id;
                        $seller_price = isset($_POST['seller_price'][$seller_id]) ? (float)$_POST['seller_price'][$seller_id] : $price;
                        $seller_stock = isset($_POST['seller_stock'][$seller_id]) ? (int)$_POST['seller_stock'][$seller_id] : $stock;
                        
                        $stmt = $conn->prepare("INSERT INTO product_sellers (product_id, seller_id, seller_price, seller_stock) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iidi", $product_id, $seller_id, $seller_price, $seller_stock);
                        $stmt->execute();
                    }
                }
            }
            
            // Handle categories (many-to-many)
            if ($product_id) {
                // Remove existing categories
                $stmt = $conn->prepare("DELETE FROM product_categories WHERE product_id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                
                // Add new categories
                foreach ($category_ids as $category_id) {
                    $category_id = (int)$category_id;
                    $stmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $product_id, $category_id);
                    $stmt->execute();
                }
            }
            
            header('Location: products.php');
            exit;
        }
    }
}

// Get categories and sellers for dropdown
$categories = getCategories();
$sellers = getSellers();

$page_title = 'Product Management';
$base_url = '../';
$hide_nav = true;
include '../includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
    <a href="products.php?action=add" class="btn btn-primary">+ Add New Product</a>
</div>

<?php if ($action === 'add' || $action === 'edit'): 
    $product = null;
    $selected_categories = [];
    $selected_sellers = [];
    
    if ($action === 'edit' && $product_id) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            header('Location: products.php');
            exit;
        }
        
        // Get selected categories
        $stmt = $conn->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $selected_categories[] = $row['category_id'];
        }
        
        // Get selected sellers with their prices and stocks
        $stmt = $conn->prepare("SELECT seller_id, seller_price, seller_stock FROM product_sellers WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $selected_sellers[$row['seller_id']] = [
                'price' => $row['seller_price'],
                'stock' => $row['seller_stock']
            ];
        }
    }
?>

<div class="form-container">
    <h1><?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?></h1>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Name:</label>
            <input type="text" name="name" value="<?php echo $product ? htmlspecialchars($product['name']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" required><?php echo $product ? htmlspecialchars($product['description']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Base Price:</label>
            <input type="number" name="price" step="0.01" min="0" value="<?php echo $product ? $product['price'] : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Base Stock:</label>
            <input type="number" name="stock" min="0" value="<?php echo $product ? $product['stock'] : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Product Image:</label>
            <?php if ($product && $product['image']): ?>
                <div style="margin-bottom: 10px;">
                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="Current image" 
                         style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                    <p style="font-size: 0.9rem; color: #666;">Current image (leave empty to keep)</p>
                </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small style="color: #666;">Allowed formats: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)</small>
        </div>
        
        <div class="form-group">
            <label>Categories:</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 10px;">
                <?php 
                $categories->data_seek(0);
                while ($category = $categories->fetch_assoc()): 
                ?>
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" 
                               name="categories[]" 
                               value="<?php echo $category['id']; ?>"
                               <?php echo in_array($category['id'], $selected_categories) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </label>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label>Sellers (Multiple sellers can sell this product):</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                <?php 
                $sellers->data_seek(0);
                while ($seller = $sellers->fetch_assoc()): 
                ?>
                    <label style="display: flex; flex-direction: column; gap: 5px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <div>
                            <input type="checkbox" 
                                   name="sellers[]" 
                                   value="<?php echo $seller['id']; ?>"
                                   <?php echo isset($selected_sellers[$seller['id']]) ? 'checked' : ''; ?>
                                   onchange="toggleSellerFields(<?php echo $seller['id']; ?>, this.checked)">
                            <strong><?php echo htmlspecialchars($seller['full_name']); ?></strong>
                        </div>
                        <div id="seller_<?php echo $seller['id']; ?>_fields" 
                             style="<?php echo isset($selected_sellers[$seller['id']]) ? '' : 'display: none;'; ?>">
                            <input type="number" 
                                   name="seller_price[<?php echo $seller['id']; ?>]" 
                                   step="0.01" 
                                   placeholder="Price"
                                   value="<?php echo isset($selected_sellers[$seller['id']]) ? $selected_sellers[$seller['id']]['price'] : ($product['price'] ?? ''); ?>"
                                   style="width: 100%; padding: 5px; margin-top: 5px;">
                            <input type="number" 
                                   name="seller_stock[<?php echo $seller['id']; ?>]" 
                                   placeholder="Stock"
                                   value="<?php echo isset($selected_sellers[$seller['id']]) ? $selected_sellers[$seller['id']]['stock'] : ''; ?>"
                                   style="width: 100%; padding: 5px; margin-top: 5px;">
                        </div>
                    </label>
                <?php endwhile; ?>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
        </button>
        <a href="products.php" class="btn btn-secondary"><?php echo t('button_cancel'); ?></a>
    </form>
</div>

<?php else: // List products ?>

<h1>Product Management</h1>

<?php
$stmt = $conn->prepare("SELECT DISTINCT p.* 
                        FROM products p 
                        ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->get_result();
?>

<table class="cart-table">
    <thead>
        <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Base Price</th>
            <th>Base Stock</th>
            <th>Sellers</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($products->num_rows > 0): ?>
            <?php while ($product = $products->fetch_assoc()): 
                // Get sellers for this product
                $seller_stmt = $conn->prepare("SELECT COUNT(*) as seller_count FROM product_sellers WHERE product_id = ?");
                $seller_stmt->bind_param("i", $product['id']);
                $seller_stmt->execute();
                $seller_count = $seller_stmt->get_result()->fetch_assoc()['seller_count'];
            ?>
                <tr>
                    <td>
                        <?php if ($product['image']): ?>
                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"
                                 onerror="this.src='../images/not_found.jpg'">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">No Image</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo formatPrice($product['price']); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td><?php echo $seller_count; ?> seller(s)</td>
                    <td>
                        <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-secondary">Edit</a>
                        <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" 
                           class="btn btn-secondary" 
                           onclick="return confirm('<?php echo t('confirm_delete_product'); ?>')"
                           style="background: #dc3545;">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No products found. <a href="products.php?action=add">Add your first product</a></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php endif; ?>

<?php
// Handle delete action
if ($action === 'delete' && $product_id) {
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    // Delete product (cascades will handle related records)
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    // Delete image file
    if ($product && $product['image'] && file_exists('../' . $product['image'])) {
        unlink('../' . $product['image']);
    }
    
    $_SESSION['success'] = t('flash_product_deleted');
    header('Location: products.php');
    exit;
}
?>

<?php include '../includes/footer.php'; ?>
<script src="../js/admin_products.js"></script>