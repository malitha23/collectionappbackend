<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../db-config.php');

// Get the updated values from the request body
$updatedValues = json_decode(file_get_contents("php://input"), true);
$formattedDateTime = $updatedValues['formattedDateTime'];
try {
    // Prepare the update statement
    $stmt = $pdo->prepare("UPDATE targetdayrangesdomestic SET day = :day, target = :target WHERE id = :id");

    // Update the values for each row
    $stmt->bindParam(':day', $day);
    $stmt->bindParam(':target', $target);
    $stmt->bindParam(':id', $id);

    // Update first row
    $day = $updatedValues['firstTargetDay'];
    $target = $updatedValues['firstTargetPercentage'];
    $id = 1;
    $stmt->execute();
    $affectedRows = $stmt->rowCount();

    // Update second row
    $day = $updatedValues['secondTargetDay'];
    $target = $updatedValues['secondTargetPercentage'];
    $id = 2;
    $stmt->execute();
    $affectedRows += $stmt->rowCount();

    // Update third row
    $day = $updatedValues['thirdTargetDay'];
    $target = $updatedValues['thirdTargetPercentage'];
    $id = 3;
    $stmt->execute();
    $affectedRows += $stmt->rowCount();

    // Update fourth row
    $day = $updatedValues['fourthTargetDay'];
    $target = $updatedValues['fourthTargetPercentage'];
    $id = 4;
    $stmt->execute();
    $affectedRows += $stmt->rowCount();

    if ($affectedRows > 0) {
        // Execution was successful
        function processNotifications($executiveCode, $currentMonthName, $formattedDateTime) {
    global $pdo;

    // Check if a notification already exists for the specified executive code and summary
    $existingNotificationSql = "SELECT COUNT(*) FROM `notifications` WHERE `excode` = :executiveCode AND `detail` = :detail";
    $existingNotificationStmt = $pdo->prepare($existingNotificationSql);
    $existingNotificationStmt->bindValue(':executiveCode', $executiveCode);
    $existingNotificationStmt->bindValue(':detail', 'Your ' . $currentMonthName . ' Collection Targets Achieved Percentage Updated!');
    $existingNotificationStmt->execute();
    $existingCount = $existingNotificationStmt->fetchColumn();

    if ($existingCount < 1) {
        // Insert the notification
        $insertNotificationSql = "INSERT INTO `notifications` (`severity`, `summary`, `detail`, `role`, `excode`, `status`, `created_at`, `displayed`) VALUES (:severity, :summary, :detail, :role, :executiveCode, '1', '$formattedDateTime', '0')";
        $notificationStmt = $pdo->prepare($insertNotificationSql);
        $notificationStmt->bindValue(':severity', 'success');
        $notificationStmt->bindValue(':summary', 'Alert');
        $notificationStmt->bindValue(':detail', 'Your ' . $currentMonthName . ' Collection Targets Achieved Percentage Updated!');
        $notificationStmt->bindValue(':role', 'all');
        $notificationStmt->bindValue(':executiveCode', $executiveCode);
        $notificationStmt->execute();
    } else {
        // Update the existing notification
        $updateNotificationSql = "UPDATE `notifications` SET `severity` = :severity, `summary` = :summary, `detail` = :detail, `created_at` = :formattedDateTime, `status`='1', `displayed` = '0' WHERE `role` = 'all' AND `excode` = :executiveCode AND `summary` = 'Alert' AND `detail` = :detail ";
        $updateNotificationStmt = $pdo->prepare($updateNotificationSql);
        $updateNotificationStmt->bindValue(':severity', 'success');
        $updateNotificationStmt->bindValue(':summary', 'Alert');
        $updateNotificationStmt->bindValue(':detail', 'Your ' . $currentMonthName . ' Collection Targets Achieved Percentage Updated!');
        $updateNotificationStmt->bindValue(':executiveCode', $executiveCode);
        $updateNotificationStmt->bindValue(':formattedDateTime', $formattedDateTime);
        $updateNotificationStmt->execute();
    }
}

$currentMonth = date('m');
    $currentYear = date('Y');

    // Prepare the SQL statement for CollectionExective
    $sqlCollectionExective = "SELECT DISTINCT `CollectionExective` FROM `collectionexectivetargetdata` WHERE `InYear` = :year AND `InMonth` = :month";
    $stmtCollectionExective = $pdo->prepare($sqlCollectionExective);
    $stmtCollectionExective->bindParam(':year', $currentYear, PDO::PARAM_INT);
    $stmtCollectionExective->bindParam(':month', $currentMonth, PDO::PARAM_INT);
    $stmtCollectionExective->execute();

    while ($row = $stmtCollectionExective->fetch(PDO::FETCH_ASSOC)) {
        $CollectionExective = $row['CollectionExective'];
        $currentMonthName = date('F');
        processNotifications($CollectionExective, $currentMonthName, $formattedDateTime);
    }

    // Prepare the SQL statement for Coordinator
    $sqlCoordinator = "SELECT DISTINCT ec.Coordinator FROM `collectionexectivetargetdata` ct INNER JOIN `executivecodes` ec ON ct.`CollectionExective` = ec.`Master_Cod` WHERE ct.`InYear` = :year AND ct.`InMonth` = :month";
    $stmtCoordinator = $pdo->prepare($sqlCoordinator);
    $stmtCoordinator->bindParam(':year', $currentYear, PDO::PARAM_INT);
    $stmtCoordinator->bindParam(':month', $currentMonth, PDO::PARAM_INT);
    $stmtCoordinator->execute();

    while ($row = $stmtCoordinator->fetch(PDO::FETCH_ASSOC)) {
        $Coordinator = $row['Coordinator'];
        $currentMonthName = date('F');
        processNotifications($Coordinator, $currentMonthName, $formattedDateTime);
    }
    
     // Prepare the SQL statement for Mangers 
    $sqlCoordinator = "SELECT DISTINCT ec.Mangers  FROM `collectionexectivetargetdata` ct INNER JOIN `executivecodes` ec ON ct.`CollectionExective` = ec.`Master_Cod` WHERE ct.`InYear` = :year AND ct.`InMonth` = :month";
    $stmtCoordinator = $pdo->prepare($sqlCoordinator);
    $stmtCoordinator->bindParam(':year', $currentYear, PDO::PARAM_INT);
    $stmtCoordinator->bindParam(':month', $currentMonth, PDO::PARAM_INT);
    $stmtCoordinator->execute();

    while ($row = $stmtCoordinator->fetch(PDO::FETCH_ASSOC)) {
        $Mangers  = $row['Mangers'];
        $currentMonthName = date('F');
        processNotifications($Mangers, $currentMonthName, $formattedDateTime);
    }

    $response = array('success' => true, 'message' => 'Values updated successfully');
    echo json_encode($response);
} else {
    // Execution failed
    $response = array('success' => false, 'message' => 'Failed to update values');
    echo json_encode($response);
}
} catch (PDOException $e) {
    $response = array('success' => false, 'message' => 'An error occurred: ' . $e->getMessage());
    echo json_encode($response);
}
?>
