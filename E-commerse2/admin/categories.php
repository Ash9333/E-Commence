<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$conn = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            $stmt->execute();
            $_SESSION['success'] = 'Category added successfully';
        } else if ($action === 'edit' && $category_id) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $category_id);
            $stmt->execute();
            $_SESSION['success'] = 'Category updated successfully';
        }
        
        header('Location: categories.php');
        exit;
    }
}

// Handle delete
if ($action === 'delete' && $category_id) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $_SESSION['success'] = 'Category deleted successfully';
    header('Location: categories.php');
    exit;
}

$page_title = 'Manage Categories';
$base_url = '../';
$hide_nav = true;
include '../includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
    <a href="categories.php?action=add" class="btn btn-primary">+ Add New Category</a>
</div>

<?php if ($action === 'add' || $action === 'edit'): 
    $category = null;
    if ($action === 'edit' && $category_id) {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
    }
?>

<div class="form-container">
    <h1><?php echo $action === 'add' ? 'Add New Category' : 'Edit Category'; ?></h1>
    
    <form method="POST">
        <div class="form-group">
            <label>Category Name:</label>
            <input type="text" name="name" value="<?php echo $category ? htmlspecialchars($category['name']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" required><?php echo $category ? htmlspecialchars($category['description']) : ''; ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <?php echo $action === 'add' ? 'Add Category' : 'Update Category'; ?>
        </button>
        <a href="categories.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php else: ?>

<h1>Manage Categories</h1>

<?php
$stmt = $conn->prepare("SELECT c.*, COUNT(pc.product_id) as product_count 
                        FROM categories c 
                        LEFT JOIN product_categories pc ON c.id = pc.category_id 
                        GROUP BY c.id 
                        ORDER BY c.name");
$stmt->execute();
$categories = $stmt->get_result();
?>

<table class="cart-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Products</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($categories->num_rows > 0): ?>
            <?php while ($category = $categories->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $category['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                    <td><?php echo $category['product_count']; ?> product(s)</td>
                    <td><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></td>
                    <td>
                        <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-secondary">Edit</a>
                        <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" 
                           class="btn btn-secondary" 
                           onclick="return confirm('Are you sure you want to delete this category?')"
                           style="background: #dc3545;">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No categories found. <a href="categories.php?action=add">Add your first category</a></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>