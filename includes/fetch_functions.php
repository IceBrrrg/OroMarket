<?php
require_once 'db_connect.php';

function fetchCategories() {
    global $pdo;
    try {
        $sql = "SELECT id, name, description, image FROM categories WHERE is_active = 1 ORDER BY name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}
?>