<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Validate inputs (you can add more checks)
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        // Here you can send the email or save the data to a database
        // For example, using mail() function (make sure to configure your server)
        $to = 'your_email@example.com'; // Replace with your email address
        $headers = "From: $name <$email>\r\n";
        $mailSent = mail($to, $subject, $message, $headers);

        if ($mailSent) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mail sending failed.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
