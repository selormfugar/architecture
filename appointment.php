<?php

require_once 'config.php'; // Path to your config file outside web root

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Ensure you have the correct path to autoload.php

// Initialize session for rate limiting
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Implement rate limiting
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $current_time = time();
    $max_requests_per_hour = 5;

    // Initialize or update session data for this IP
    if (!isset($_SESSION['submissions'][$user_ip])) {
        $_SESSION['submissions'][$user_ip] = [
            'count' => 0,
            'first_time' => $current_time
        ];
    }

    // Check if an hour has passed since first submission
    if (($current_time - $_SESSION['submissions'][$user_ip]['first_time']) > 3600) {
        // Reset counter if it's been more than an hour
        $_SESSION['submissions'][$user_ip] = [
            'count' => 0,
            'first_time' => $current_time
        ];
    }

    // Increment submission count
    $_SESSION['submissions'][$user_ip]['count']++;

    // Check if too many submissions
    if ($_SESSION['submissions'][$user_ip]['count'] > $max_requests_per_hour) {
        echo json_encode(['status' => 'error', 'message' => 'Too many appointment requests. Please try again later.']);
        exit;
    }

    // 2. Check for honeypot (you'll need to add a hidden field to your form named "website" or similar)
    if (isset($_POST['website']) && !empty($_POST['website'])) {
        // This is a bot - return success to fool the bot but don't process
        echo json_encode(['status' => 'success']);
        exit;
    }

    // 3. Better input validation and sanitization
    $name = filter_var(trim($_POST['name'] ?? ''), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mobile = filter_var(trim($_POST['mobile'] ?? ''), FILTER_SANITIZE_STRING);
    $service = filter_var(trim($_POST['service'] ?? ''), FILTER_SANITIZE_STRING);
    $date = filter_var(trim($_POST['date'] ?? ''), FILTER_SANITIZE_STRING);
    $time = filter_var(trim($_POST['time'] ?? ''), FILTER_SANITIZE_STRING);
    $message = filter_var(trim($_POST['message'] ?? ''), FILTER_SANITIZE_STRING);

    // Properly validate email
    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
        exit;
    }

    // 4. Check for spam patterns in message
    $spam_patterns = [
        '/\bHello\b/i',
        '/\bAlice\b/i',
        '/\bJohn\b/i',
        '/\bI\s+write\b/i',
        '/\bTestUser\b/i',
        '/\bMyName\b/i',
        // Add more patterns as needed
    ];

    foreach ($spam_patterns as $pattern) {
        if (preg_match($pattern, $message) || preg_match($pattern, $service)) {
            // Silently reject spam but tell the sender it worked
            echo json_encode(['status' => 'success']);
            exit;
        }
    }

    // 5. Check for valid phone number (basic check for now)
    if (!preg_match('/^[0-9\-\(\)\s\+\.]{7,20}$/', $mobile)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid phone number.']);
        exit;
    }

    // 6. Validate date and time formats
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid date.']);
        exit;
    }

    // Validate inputs
    if (!empty($name) && !empty($email) && !empty($mobile) && !empty($service) && !empty($date) && !empty($time)) {
        $mail = new PHPMailer(true); // Create a new PHPMailer instance
        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debugging in production (set to 2 for testing)
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_username;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Set sender (From address)
            $mail->setFrom('raak@raakac.org', 'RAAKAC Appointment System');
            $mail->addReplyTo($email, $name); // Set reply-to as the customer's email
            
            // Prepare admin email with enhanced information
            $admin_message = "
            <h2>New Appointment Request</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Mobile:</strong> $mobile</p>
            <p><strong>Service:</strong> $service</p>
            <p><strong>Date:</strong> $date</p>
            <p><strong>Time:</strong> $time</p>
            <p><strong>IP Address:</strong> {$_SERVER['REMOTE_ADDR']}</p>
            <p><strong>Date/Time of Request:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Browser:</strong> {$_SERVER['HTTP_USER_AGENT']}</p>";
            
            if (!empty($message)) {
                $admin_message .= "<h3>Additional Message:</h3><p>$message</p>";
            }
            
            // Plain text version
            $admin_text_message = "New Appointment Request\n\n"
                . "Name: $name\n"
                . "Email: $email\n"
                . "Mobile: $mobile\n"
                . "Service: $service\n"
                . "Date: $date\n"
                . "Time: $time\n"
                . "IP Address: {$_SERVER['REMOTE_ADDR']}\n"
                . "Date/Time of Request: " . date('Y-m-d H:i:s') . "\n";
                
            if (!empty($message)) {
                $admin_text_message .= "Additional Message:\n$message\n";
            }
            
            // Send email to the site owner
            $mail->addAddress('raak@raakac.org'); // Site owner's email
            $mail->isHTML(true);
            $mail->Subject = "New Appointment: $service on $date";
            $mail->Body = $admin_message;
            $mail->AltBody = $admin_text_message;
            $mail->send();
            
            // Confirmation email to the visitor
            $visitor_message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px;'>
                <h2>Appointment Confirmation</h2>
                <p>Dear $name,</p>
                <p>Thank you for booking an appointment with us.</p>
                <p><strong>Service:</strong> $service</p>
                <p><strong>Date:</strong> $date</p>
                <p><strong>Time:</strong> $time</p>
                <p>We will contact you shortly to confirm your appointment.</p>
                <p>If you need to make any changes, please call us or reply to this email.</p>
                <p>Best regards,<br>RAAKAC Team</p>
            </div>";
            
            $visitor_text_message = "Appointment Confirmation\n\n"
                . "Dear $name,\n\n"
                . "Thank you for booking an appointment with us.\n\n"
                . "Service: $service\n"
                . "Date: $date\n"
                . "Time: $time\n\n"
                . "We will contact you shortly to confirm your appointment.\n\n"
                . "If you need to make any changes, please call us or reply to this email.\n\n"
                . "Best regards,\nRAAKAC Team";
            
            // Send confirmation email to visitor
            $mail->clearAddresses(); // Clear the previous recipient
            $mail->addAddress($email); // Visitor's email
            $mail->Subject = "Your Appointment Confirmation";
            $mail->Body = $visitor_message;
            $mail->AltBody = $visitor_text_message;
            $mail->send();
            
            // Respond with success
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            // Use PHPMailer error info for better debugging
            echo json_encode(['status' => 'error', 'message' => 'Mail sending failed: ' . $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}