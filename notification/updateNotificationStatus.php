<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

// Get the request payload
$requestPayload = file_get_contents("php://input");
$notificationid = json_decode($requestPayload);

// Extract the notification ID
$notificationid = $notificationid->notificationid;

// Prepare the SQL query to update the displayed status
$sqlUpdate = "UPDATE `notifications` SET `displayed` = '1' WHERE `id` = :notificationId";

// Prepare the statement for updating
$stmtUpdate = $pdo->prepare($sqlUpdate);
$stmtUpdate->bindValue(':notificationId', $notificationid);

// Execute the update statement
$updateSuccess = $stmtUpdate->execute();


?>
