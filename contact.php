<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes
require 'vendor/autoload.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Validate inputs
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();                                           // Use SMTP
            $mail->Host       = 'smtp.gmail.com';                      // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                  // Enable SMTP authentication
            $mail->Username   = 'josefsfugar@gmail.com';                // Your Gmail email
            $mail->Password   = 'ntrkejktwgmjjvwm';                   // Your Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;        // Enable TLS encryption
            $mail->Port       = 587;                                   // TCP port to connect to

            //Recipients
            $mail->setFrom($email, $name);                             // Sender email and name
            $mail->addAddress('trapbosy@gmail.com');               // Add recipient (site owner)
            $mail->addReplyTo($email, $name);                          // Reply-to email

            // Content
            $mail->isHTML(true);                                       // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = "Message from $name ($email):<br><br>$message";
            $mail->AltBody = "Message from $name ($email):\n\n$message";  // Fallback for non-HTML email clients

            // Send the email
            $mail->send();

            // Send confirmation to the visitor
            $mail->clearAddresses();                                  // Clear previous addresses
            $mail->addAddress($email);                                // Send to the user (visitor)
            $mail->Subject = 'Thank you for contacting us!';
            $mail->Body = "Hello $name,<br><br>Thank you for reaching out! We have received your message and will get back to you soon.";
            $mail->AltBody = "Hello $name,\n\nThank you for reaching out! We have received your message and will get back to you soon.";

            $mail->send();

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
