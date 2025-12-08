<?php
require_once 'config/database.php';
require_once 'js/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDB();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $language = isset($_POST['language']) ? $_POST['language'] : 'en';
        $languages = getSupportedLanguages();
        if (!array_key_exists($language, $languages)) {
            $language = 'en';
        }

        if ($full_name === '' || $username === '' || $email === '') {
            $profile_error = t('profile_update_required_error');
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->bind_param("ssi", $username, $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $profile_error = t('profile_update_username_email_taken');
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, address = ?, language = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $full_name, $username, $email, $phone, $address, $language, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $username;
                    setCurrentLanguage($language);
                    $profile_success = t('profile_update_success');
                } else {
                    $profile_error = t('profile_update_failed');
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $password_error = t('profile_password_mismatch');
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            if (!$user || !password_verify($current_password, $user['password'])) {
                $password_error = t('profile_current_password_incorrect');
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);
                if ($stmt->execute()) {
                    $password_success = t('profile_password_update_success');
                } else {
                    $password_error = t('profile_password_update_failed');
                }
            }
        }
    }
}

$stmt = $conn->prepare("SELECT username, email, full_name, phone, address, role, created_at, language FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$page_title = t('profile_title');
include 'js/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-top: 30px;">
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h2><?php echo t('profile_account_overview'); ?></h2>
        <p><strong><?php echo t('profile_name_label'); ?></strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
        <p><strong><?php echo t('profile_username_label'); ?></strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong><?php echo t('profile_email_label'); ?></strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <?php if (!empty($user['phone'])): ?>
            <p><strong><?php echo t('profile_phone_label'); ?></strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <?php endif; ?>
        <?php if (!empty($user['address'])): ?>
            <p><strong><?php echo t('profile_address_label'); ?></strong><br><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
        <?php endif; ?>
        <p><strong><?php echo t('profile_role_label'); ?></strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
        <p><strong><?php echo t('profile_member_since_label'); ?></strong> <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
    </div>

    <div>
        <?php if (isset($profile_error)): ?>
            <div class="alert alert-error"><?php echo $profile_error; ?></div>
        <?php endif; ?>
        <?php if (isset($profile_success)): ?>
            <div class="alert alert-success"><?php echo $profile_success; ?></div>
        <?php endif; ?>
        <?php if (isset($password_error)): ?>
            <div class="alert alert-error"><?php echo $password_error; ?></div>
        <?php endif; ?>
        <?php if (isset($password_success)): ?>
            <div class="alert alert-success"><?php echo $password_success; ?></div>
        <?php endif; ?>

        <div class="form-container" style="margin: 0 0 30px 0; max-width: 100%;">
            <h2><?php echo t('profile_update_profile_title'); ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label><?php echo t('profile_full_name_label'); ?></label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('profile_username_label'); ?></label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('profile_email_label'); ?></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('profile_phone_label'); ?></label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label><?php echo t('profile_address_label'); ?></label>
                    <textarea name="address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo t('profile_language_label'); ?>:</label>
                    <select name="language">
                        <?php foreach (getSupportedLanguages() as $code => $label): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($user['language'] ?? 'en') === $code ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary"><?php echo t('profile_update_profile_button'); ?></button>
            </form>
        </div>

        <div class="form-container" style="margin: 0; max-width: 100%;">
            <h2><?php echo t('profile_change_password_title'); ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label><?php echo t('profile_current_password_label'); ?></label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('profile_new_password_label'); ?></label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label><?php echo t('profile_confirm_new_password_label'); ?></label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-secondary"><?php echo t('profile_update_password_button'); ?></button>
            </form>
        </div>
    </div>
</div>

<?php include 'js/includes/footer.php'; ?>
