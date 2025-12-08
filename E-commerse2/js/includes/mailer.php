<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Load mail configuration
$mailConfig = require dirname(__DIR__, 2) . '/config/mail.php';

function createMailer(): PHPMailer {
    global $mailConfig;
    
    $mail = new PHPMailer(true);
    $smtp = $mailConfig['smtp'];

    $mail->isSMTP();
    $mail->Host = $smtp['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];
    $mail->Port = $smtp['port'];
    $mail->SMTPSecure = $smtp['encryption'];
    $mail->setFrom($smtp['from']['email'], $smtp['from']['name']);
    $mail->isHTML(true);

    return $mail;
}

function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool {
    $mail = createMailer();

    try {
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = 'Password Reset Request';

        $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $mail->Body =
            '<p>Hello ' . $safeName . ',</p>' .
            '<p>You requested to reset your password. Click the link below to set a new password:</p>' .
            '<p><a href="' . $safeLink . '">' . $safeLink . '</a></p>' .
            '<p>This link will expire in 30 minutes. If you did not request this, you can safely ignore this email.</p>';

        $mail->AltBody =
            "Hello {$toName},\n\n" .
            "You requested to reset your password. Use the link below to set a new password:\n" .
            "{$resetLink}\n\n" .
            "This link will expire in 30 minutes. If you did not request this, you can ignore this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Password reset email error: ' . $mail->ErrorInfo);
        return false;
    }
}

function sendOrderCancelledEmail(string $toEmail, string $toName, int $orderId, float $totalAmount): bool {
    $mail = createMailer();

    try {
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = 'Your order #' . $orderId . ' has been cancelled';

        $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeOrderId = htmlspecialchars('#' . $orderId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $formattedTotal = '$' . number_format($totalAmount, 2);
        $safeTotal = htmlspecialchars($formattedTotal, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $mail->Body =
            '<p>Hello ' . $safeName . ',</p>' .
            '<p>Your order ' . $safeOrderId . ' has been <strong>cancelled</strong>.</p>' .
            '<p>Order total: ' . $safeTotal . '</p>' .
            '<p>If you have any questions, you can reply to this email.</p>';

        $mail->AltBody =
            "Hello {$toName},\n\n" .
            "Your order #{$orderId} has been cancelled.\n" .
            "Order total: {$formattedTotal}\n\n" .
            "If you have any questions, you can reply to this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Order cancelled email error: ' . $mail->ErrorInfo);
        return false;
    }
}
