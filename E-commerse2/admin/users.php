<?php
require_once '../config/database.php';
require_once '../js/includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$conn = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_role') {
    $user_id = (int)$_POST['user_id'];
    $role = sanitize($_POST['role']);
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $user_id);
    $stmt->execute();
    
    $_SESSION['success'] = 'User role updated successfully';
    header('Location: users.php');
    exit;
}

// Handle delete
if ($action === 'delete' && $user_id) {
    // Don't allow deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = 'You cannot delete your own account';
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($orderCount);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM order_items WHERE seller_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($orderItemCount);
        $stmt->fetch();
        $stmt->close();

        if ($orderCount > 0 || $orderItemCount > 0) {
            if ($orderCount > 0 && $orderItemCount > 0) {
                $_SESSION['error'] = 'Cannot delete this user because they have existing orders and sales records.';
            } elseif ($orderCount > 0) {
                $_SESSION['error'] = 'Cannot delete this user because they have existing orders.';
            } else {
                $_SESSION['error'] = 'Cannot delete this user because they have sales records in orders.';
            }
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $_SESSION['success'] = 'User deleted successfully';
        }
    }
    header('Location: users.php');
    exit;
}

$page_title = 'Manage Users';
$base_url = '../';
$hide_nav = true;
include '../js/includes/header.php';

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div class="page-actions mb-4">
    <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<h1>Manage Users</h1>

<?php
// Get all users
$stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->get_result();
?>

<table class="cart-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($users->num_rows > 0): ?>
            <?php while ($user = $users->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <select name="role" class="table-select" onchange="this.form.submit()">
                                <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="seller" <?php echo $user['role'] === 'seller' ? 'selected' : ''; ?>>Seller</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        <?php else: ?>
                            <span class="text-muted">Current User</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center p-4">No users found.</td>
                <td colspan="7" style="text-align: center; padding: 20px;">No users found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../js/includes/footer.php'; ?>