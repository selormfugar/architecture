<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars(trim($_POST['email']));

    // Validate email
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Here you can save the email to a database or send a confirmation email
        // Example: Save to database
        // Assuming you have a database connection already set up
        // $query = "INSERT INTO newsletter_subscribers (email) VALUES ('$email')";
        // $result = mysqli_query($connection, $query);

        // Send confirmation email
        $subject = "Subscription Confirmation";
        $message = "Thank you for subscribing to our newsletter!\n\nYou will receive updates about our latest news and services.";
        $headers = "From: info@construction.com"; // Change this to your sending email address

        if (mail($email, $subject, $message, $headers)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Unable to send confirmation email.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
