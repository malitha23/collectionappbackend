<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

// Get the request payload
$requestPayload = file_get_contents("php://input");
$profileData = json_decode($requestPayload);

// Extract the login data
$role = $profileData->role;
$executiveCode = $profileData->executiveCode;

if ($role == "Head") {
  $query = "SELECT * FROM `notifications` WHERE `role` = :role AND `excode` = :executiveCode  AND (`status` = '1' OR `status` = '2') AND `displayed` = '1' ORDER BY `created_at` DESC";
}else{
  $query = "SELECT * FROM `notifications` WHERE (`role` = 'all' OR `role` = :role) AND (`excode` = 'all' OR `excode` = :executiveCode) AND (`status` = '1' OR `status` = '2') AND `displayed` = '1' ORDER BY `created_at` DESC";
}

// Prepare the SQL query
$sql = $query;

// Prepare the statement
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':role', $role);
$stmt->bindValue(':executiveCode', $executiveCode);

// Execute the statement
if ($stmt->execute()) {
  // Fetch all rows as an associative array
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Print the array of rows as JSON
  echo json_encode($rows);
} else {
  // Query failed
  echo 'Error: ' . $stmt->errorInfo()[2];
}
?>
