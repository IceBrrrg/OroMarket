<?php
// Database connection parameters for PDO
$host = 'localhost';
$dbname = 'oroquieta_marketplace';
$username = 'root';        // Default XAMPP MySQL username
$password = '';            // Default XAMPP MySQL password (empty by default)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for better security and performance
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
    // echo "PDO Connection Successful!"; // Uncomment for testing, then remove
} catch (\PDOException $e) {
    // If connection fails, output the error and stop script execution
    die("Database connection failed: " . $e->getMessage() . "<br>Please check your db_connect.php settings (database name, username, password).");
    // In a production environment, you would log the error and display a generic message:
    // error_log("Database connection error: " . $e->getMessage());
    // header("Location: /error_page.php"); // Redirect to a user-friendly error page
    // exit();
}
?>