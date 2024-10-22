<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure you have the correct path to autoload.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $mobile = htmlspecialchars(trim($_POST['mobile']));
    $service = htmlspecialchars(trim($_POST['service']));
    $date = htmlspecialchars(trim($_POST['date']));
    $time = htmlspecialchars(trim($_POST['time']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Validate inputs
    if (!empty($name) && !empty($email) && !empty($mobile) && !empty($service) && !empty($date) && !empty($time)) {
        $mail = new PHPMailer(true); // Create a new PHPMailer instance

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'josefsfugar@gmail.com';
            $mail->Password   = 'ntrkejktwgmjjvwm';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Email to the site owner
            $to_owner = 'aansong@raakac.org'; // Replace with your email
            $owner_subject = "New Appointment Request from $name";
            $owner_message = "Appointment Details:\n"
                . "Name: $name\n"
                . "Email: $email\n"
                . "Mobile: $mobile\n"
                . "Service: $service\n"
                . "Date: $date\n"
                . "Time: $time\n"
                . "Message:\n$message";

            // Send email to the site owner
            $mail->setFrom($email, $name);
            $mail->addAddress($to_owner); // Site owner's email
            $mail->Subject = $owner_subject;
            $mail->Body    = nl2br($owner_message); // Convert line breaks to HTML
            $mail->AltBody = $owner_message; // Non-HTML version

            // Send email to the site owner
            $mail->send();

            // Confirmation email to the visitor
            $to_visitor = $email;
            $visitor_subject = "Appointment Confirmation";
            $visitor_message = "Dear $name,\n\nThank you for booking an appointment for '$service'."
                . "\nYour appointment is scheduled for $date at $time."
                . "\nWe will get back to you shortly.\n\nBest regards,\nYour Company";

            // Send confirmation email to visitor
            $mail->clearAddresses(); // Clear the previous recipient
            $mail->setFrom('no-reply@example.com', 'Your Company'); // Customize the From field
            $mail->addAddress($to_visitor); // Visitor's email
            $mail->Subject = $visitor_subject;
            $mail->Body    = nl2br($visitor_message); // Convert line breaks to HTML
            $mail->AltBody = $visitor_message; // Non-HTML version

            // Send confirmation email to visitor
            $mail->send();

            // Respond with success
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Mail sending failed: ' . $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
