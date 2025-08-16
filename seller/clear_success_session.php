<?php
session_start();

// Clear the success session variables
if (isset($_SESSION['registration_success'])) {
    unset($_SESSION['registration_success']);
}
if (isset($_SESSION['registered_username'])) {
    unset($_SESSION['registered_username']);
}
if (isset($_SESSION['selected_stall_number'])) {
    unset($_SESSION['selected_stall_number']);
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Session cleared']);
?>