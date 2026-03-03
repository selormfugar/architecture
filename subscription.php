<?php

require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    $smtp_host = getenv('SMTP_HOST');
    $smtp_port = (int) (getenv('SMTP_PORT') ?: 587);
    $smtp_username = getenv('SMTP_USERNAME');
    $smtp_password = getenv('SMTP_PASSWORD');
    $smtp_encryption = getenv('SMTP_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
    $mail_from_address = getenv('SUBSCRIPTION_FROM_ADDRESS') ?: (getenv('MAIL_FROM_ADDRESS') ?: $smtp_username);
    $mail_from_name = getenv('SUBSCRIPTION_FROM_NAME') ?: 'RAAKAC Newsletter';

    if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
        echo json_encode(['status' => 'error', 'message' => 'SMTP configuration is incomplete.']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_encryption;
        $mail->Port = $smtp_port;

        $mail->setFrom($mail_from_address, $mail_from_name);
        $mail->addAddress($email);
        $mail->isHTML(false);
        $mail->Subject = 'Subscription Confirmation';
        $mail->Body = "Thank you for subscribing to our newsletter!\n\nYou will receive updates about our latest news and services.";

        $mail->send();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Unable to send confirmation email.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
