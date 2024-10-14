<?php
// Database connection parameters
$host = 'localhost';      // e.g., localhost
$db = 'raak-architecture-postgres';    // Your database name
$user = 'your_username';  // Your database username
$pass = 'your_password';  // Your database password
$port = '5432';           // Default PostgreSQL port (can be omitted if using default)

// Set the Data Source Name (DSN)
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $user, $pass);

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to the PostgreSQL database successfully!";
} catch (PDOException $e) {
    // Handle connection errors
    echo "Connection failed: " . $e->getMessage();
}
