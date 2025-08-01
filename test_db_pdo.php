<?php
require_once 'includes/db_connect.php'; // Correct path from project root
if (isset($pdo) && $pdo instanceof PDO) {
    echo "<h1>Database connection from test_db_pdo.php: <span style='color: green;'>Successful!</span></h1>";
} else {
    echo "<h1>Database connection from test_db_pdo.php: <span style='color: red;'>FAILED!</span></h1>";
}
?>