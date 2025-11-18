<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDB();
$user_id = $_SESSION['user_id'];

// Get current user language from DB if available
$stmt = $conn->prepare("SELECT language, role FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$currentLanguage = $user['language'] ?? getCurrentLanguage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $language = $_POST['language'] ?? 'en';
    $languages = getSupportedLanguages();
    if (!array_key_exists($language, $languages)) {
        $language = 'en';
    }

    $stmt = $conn->prepare("UPDATE users SET language = ? WHERE id = ?");
    $stmt->bind_param('si', $language, $user_id);
    $stmt->execute();

    setCurrentLanguage($language);

    if (($user['role'] ?? '') === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$page_title = t('language_select_title');
include 'includes/header.php';
?>

<div class="form-container" style="max-width: 400px;">
    <h1><?php echo t('language_select_title'); ?></h1>
    <p style="margin-bottom: 20px;">
        <?php echo t('language_select_intro'); ?>
    </p>

    <form method="POST">
        <div class="form-group">
            <label><?php echo t('profile_language_label'); ?>:</label>
            <select name="language" required>
                <?php foreach (getSupportedLanguages() as $code => $label): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $currentLanguage === $code ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo t('language_save'); ?></button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
