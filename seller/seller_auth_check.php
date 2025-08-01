<?php
// seller_auth_check.php - Include this at the top of protected seller pages

session_start();
require_once '../includes/db_connect.php';

// Check if seller is logged in
if (!isset($_SESSION['seller_id']) || !isset($_SESSION['seller_username'])) {
    header("Location: login.php");
    exit();
}

// Verify seller status from database
try {
    $stmt = $pdo->prepare("SELECT status, is_active FROM sellers WHERE id = ?");
    $stmt->execute([$_SESSION['seller_id']]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$seller) {
        // Seller not found, destroy session and redirect
        session_destroy();
        header("Location: login.php?error=account_not_found");
        exit();
    }
    
    // Check if seller is approved and active
    if ($seller['status'] !== 'approved' || $seller['is_active'] != 1) {
        // Seller not approved or inactive, destroy session and redirect
        session_destroy();
        
        $status_messages = [
            'pending' => 'Your account is still pending approval.',
            'rejected' => 'Your account application has been rejected.',
            'suspended' => 'Your account has been suspended.'
        ];
        
        $message = $status_messages[$seller['status']] ?? 'Your account is not active.';
        header("Location: login.php?error=" . urlencode($message));
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Auth check error: " . $e->getMessage());
    session_destroy();
    header("Location: login.php?error=system_error");
    exit();
}
?>