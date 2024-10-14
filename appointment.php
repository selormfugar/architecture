<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $mobile = htmlspecialchars(trim($_POST['mobile']));
    $service = htmlspecialchars(trim($_POST['service']));
    $date = htmlspecialchars(trim($_POST['date']));
    $time = htmlspecialchars(trim($_POST['time']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Validate inputs (you can add more checks)
    if (!empty($name) && !empty($email) && !empty($mobile) && !empty($service) && !empty($date) && !empty($time) && !empty($message)) {
        // Here you can send the email or save the data to a database
        // For example, using mail() function (make sure to configure your server)
        $to = 'your_email@example.com'; // Replace with your email address
        $subject = "Appointment Booking from $name";
        $body = "Name: $name\nEmail: $email\nMobile: $mobile\nService: $service\nDate: $date\nTime: $time\nMessage: $message";
        $headers = "From: $name <$email>\r\n";
        $mailSent = mail($to, $subject, $body, $headers);

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
