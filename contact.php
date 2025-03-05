<?php

require_once 'config.php'; // Path to your config file outside web root

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes
require 'vendor/autoload.php';

// Start or resume session for token validation
session_start();

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to check if submission is from a bot
function isBot() {
    // Check if honeypot field is filled (bots often fill all fields)
    if (!empty($_POST['website'])) {
        return true;
    }
    
    // Check submission speed (bots submit forms instantly)
    if (isset($_SESSION['form_time']) && (time() - $_SESSION['form_time']) < 3) {
        return true;
    }
    
    // // Check token to prevent CSRF attacks
    // if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['form_token']) {
    //     return true;
    // }
    
    return false;
}

// Set form time when page is loaded
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['form_time'] = time();
    $_SESSION['form_token'] = bin2hex(random_bytes(32)); // Generate CSRF token
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for bot activity first
    if (isBot()) {
        echo 'Error: Form submission blocked.';
        exit;
    }
    
    // Rate limiting check
    $ip = $_SERVER['REMOTE_ADDR'];
    $rateLimitFile = 'rate_limit.json';
    $rateLimitData = [];
    
    if (file_exists($rateLimitFile)) {
        $rateLimitData = json_decode(file_get_contents($rateLimitFile), true);
    }
    
    // Clean up old entries (older than 24 hours)
    foreach ($rateLimitData as $ipAddress => $timestamps) {
        $rateLimitData[$ipAddress] = array_filter($timestamps, function($time) {
            return $time > (time() - 86400);
        });
    }
    
    // Check rate limit (max 5 emails per IP per 24 hours)
    if (isset($rateLimitData[$ip]) && count($rateLimitData[$ip]) >= 5) {
        echo 'Error: Rate limit exceeded. Please try again later.';
        exit;
    }
    
    // Get form values with stronger sanitization
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    // // Validate reCAPTCHA if implemented
    // if (isset($_POST['g-recaptcha-response'])) {
    //     $recaptcha_secret = getenv('RECAPTCHA_SECRET_KEY');
    //     $recaptcha_response = $_POST['g-recaptcha-response'];
        
    //     $recaptcha = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptcha_secret&response=$recaptcha_response");
    //     $recaptcha = json_decode($recaptcha);
        
    //     if (!$recaptcha->success) {
    //         echo 'Error: reCAPTCHA verification failed.';
    //         exit;
    //     }
    // }
    
    // Enhanced validation
    if (empty($name) || strlen($name) > 100) {
        echo 'Error: Please provide a valid name (maximum 100 characters).';
        exit;
    }
    
    if (empty($email) || !isValidEmail($email)) {
        echo 'Error: Please provide a valid email address.';
        exit;
    }
    
    if (empty($subject) || strlen($subject) > 200) {
        echo 'Error: Please provide a valid subject (maximum 200 characters).';
        exit;
    }
    
    if (empty($message) || strlen($message) < 10 || strlen($message) > 2000) {
        echo 'Error: Message must be between 10 and 2000 characters.';
        exit;
    }
    
    // Spam word filter - basic example
    $spam_keywords = ['Alice', 'John', 'TestUser', 'MyName', 'Hello', 'I write'];
    $combined_input = strtolower($name . ' ' . $subject . ' ' . $message);
    foreach ($spam_keywords as $keyword) {
        if (strpos($combined_input, $keyword) !== false) {
            echo 'Error: Your message contains prohibited content.';
            exit;   
        }
    }
    
    // Email sending with enhanced error handling
    try {
        $mail = new PHPMailer(true);
        
        // Get SMTP settings from environment variables (set in config.php)
        $smtp_host = getenv('SMTP_HOST');
        $smtp_username = getenv('SMTP_USERNAME');
        $smtp_password = getenv('SMTP_PASSWORD');
        
        // Verify environment variables are set
        if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
            throw new Exception("SMTP configuration is incomplete. Check your environment variables.");
        }
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients - use your organizational email
        $mail->setFrom($smtp_username, 'GMNOA Contact Form');
        $mail->addAddress('gmnoa@gmnoa.org'); // Primary recipient
        $mail->addReplyTo($email, $name);
        
        // Content with additional logging info
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct';
        // gmnoa_itfgh@yahoo.co.uk
        $mail->isHTML(true);
        $mail->Subject = "[Contact Form] " . $subject;
        $mail->Body    = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong><br>$message</p>
            <hr>
            <p><small>IP: $ip_address</small><br>
            <small>User Agent: $user_agent</small><br>
            <small>Referrer: $referrer</small></p>
        ";
        $mail->AltBody = "Message from $name ($email):\n\n$message\n\nIP: $ip_address\nUser Agent: $user_agent\nReferrer: $referrer";
        
        // Send the email
        $mail->send();
        
        // Update rate limit data
        if (!isset($rateLimitData[$ip])) {
            $rateLimitData[$ip] = [];
        }
        $rateLimitData[$ip][] = time();
        file_put_contents($rateLimitFile, json_encode($rateLimitData));
        
        // Send confirmation to the visitor
        $mail->clearAddresses();
        $mail->addAddress($email);
        $mail->Subject = 'Thank you for contacting RAAKAC!';
        $mail->Body = "
            <p>Hello $name,</p>
            <p>Thank you for reaching out to RAAK  - Architectural And Construction Ltd! We have received your message and will get back to you soon.</p>
            <p>This is an automated response, please do not reply to this email.</p>
        ";
        $mail->AltBody = "Hello $name,\n\nThank you for reaching out to RAAKAC! We have received your message and will get back to you soon.\n\nThis is an automated response, please do not reply to this email.";
        $mail->send();
        
        echo 'OK';
    } catch (Exception $e) {
        // Log error but don't expose details to user
        error_log("Mailer Error: {$mail->ErrorInfo}");
        echo 'Error: Message could not be sent. Please try again later.';
    }
} else {
    // Output the hidden token field for use in your form
    $token = isset($_SESSION['form_token']) ? $_SESSION['form_token'] : '';
    echo "<input type='hidden' id='form_token' value='$token'>";
}