<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    // Include PHPMailer classes
    require 'vendor/autoload.php';

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
            echo json_encode(['status' => 'error', 'message' => 'Too many submissions. Please try again later.']);
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
        $subject = filter_var(trim($_POST['subject'] ?? ''), FILTER_SANITIZE_STRING);
        $message = filter_var(trim($_POST['message'] ?? ''), FILTER_SANITIZE_STRING);

        // Properly validate email
        if (!$email) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
            exit;
        }

        // 4. Check for spam patterns in message
        $spam_patterns = [
            '/\bviagra\b/i',
            '/\bcasino\b/i',
            '/\bfree money\b/i',
            '/\btrade\s+now\b/i',
            '/\bmake money fast\b/i',
            '/\binvestment opportunity\b/i',
            // Add more patterns as needed
        ];

        foreach ($spam_patterns as $pattern) {
            if (preg_match($pattern, $message) || preg_match($pattern, $subject)) {
                // Silently reject spam but tell the sender it worked
                echo json_encode(['status' => 'success']);
                exit;
            }
        }

        // 5. Check for very short messages (often spam)
        if (strlen($message) < 10) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide a more detailed message.']);
            exit;
        }

        // Validate inputs
        if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();                                           // Use SMTP
                $mail->Host       = 'smtp.gmail.com';                      // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                  // Enable SMTP authentication
                $mail->Username   = getenv('SMTP_USERNAME') ?: 'josefsfugar@gmail.com'; // Use environment variable
                $mail->Password   = getenv('SMTP_PASSWORD') ?: 'ntrkejktwgmjjvwm';                  // Your Gmail app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;        // Enable TLS encryption
                $mail->Port       = 587;                                   // TCP port to connect to
                
                //Recipients - CHANGED: Use your email as from address to prevent spoofing
                $mail->setFrom('no-reply@raakac.org', 'RAAKAC');         // Changed sender for confirmation email                $mail->addAddress('raak@raakac.org');                      // Add recipient (site owner)
                $mail->addReplyTo($email, $name);                          // Reply-to email
                
                // Content
                $mail->isHTML(true);                                       // Set email format to HTML
                $mail->Subject = 'Contact Form: ' . $subject;              // Prefix to identify form submissions
                
                // 6. Enhanced email body with additional info to help identify spam
                $mail->Body = "
                    <h2>New message from your website contact form</h2>
                    <p><strong>Name:</strong> $name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Subject:</strong> $subject</p>
                    <p><strong>IP Address:</strong> {$_SERVER['REMOTE_ADDR']}</p>
                    <p><strong>Date/Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p><strong>User Agent:</strong> {$_SERVER['HTTP_USER_AGENT']}</p>
                    <h3>Message:</h3>
                    <p>$message</p>
                ";
                
                $mail->AltBody = "Message from $name ($email):\n\nSubject: $subject\n\n$message";
                
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
     