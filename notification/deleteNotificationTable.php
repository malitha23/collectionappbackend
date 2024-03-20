<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

try {
    $query = "DELETE FROM `notifications`";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $deletedRowCount = $stmt->rowCount();
    echo "Deleted $deletedRowCount row(s) from the notifications table.";
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}


?>
