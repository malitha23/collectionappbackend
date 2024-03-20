<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

// Get the request payload
$requestPayload = file_get_contents("php://input");
$profileData = json_decode($requestPayload);

// Extract the login data
$severity = $profileData->severity;
$msg = $profileData->msg;
$Role = $profileData->Role;
$Executivecode = $profileData->Executivecode;

try {
    $checkExcodeQuery = "SELECT COUNT(*) FROM notifications WHERE detail = ? AND excode = ?";
    $stmt = $pdo->prepare($checkExcodeQuery);
    $stmt->execute([$msg, $Executivecode]);
    $excodeExists = $stmt->fetchColumn() > 0;

    if ($excodeExists) {
        $response = array("success" => false, "message" => "Notification already exists.");
        echo json_encode($response);
    } else {
        $sql = "INSERT INTO `notifications` (`severity`, `summary`, `detail`, `role`, `excode`, `status`, `created_at`, `displayed`) VALUES (:severity, :summary, :detail, :role, :executiveCode, '1', NOW(), '0')";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':severity', $severity);
        $stmt->bindValue(':summary', 'Alert');
        $stmt->bindValue(':detail', $msg);
        $stmt->bindValue(':role', $Role);
        $stmt->bindValue(':executiveCode', $Executivecode);
        $stmt->execute();

        $response = array("success" => true, "message" => "Notification inserted successfully.");
        echo json_encode($response);
    }
} catch (PDOException $e) {
    $response = array("success" => false, "message" => "Database error: " . $e->getMessage());
    echo json_encode($response);
}

?>
