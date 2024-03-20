<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '256M'); // Adjust the value as needed

require_once('../db-config.php'); // Assuming you have your database configuration included here

$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);

$currentMonth = date('n');  // Current month (1-12)
$currentYear = date('Y');   // Current year

// Calculate the month and year of the date four months ago
$fourMonthsAgoMonth = $currentMonth - 4;
$fourMonthsAgoYear = $currentYear;

if ($fourMonthsAgoMonth <= 0) {
    $fourMonthsAgoMonth += 12;
    $fourMonthsAgoYear--;
}


if ($inputData === null) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid JSON data'));
    exit();
}

try {
// Process the received JSON data
$response = 'success'; // Initialize the response as "success"

$deleteQuery = "DELETE FROM collectionexectivetargetdata WHERE InYear = '$fourMonthsAgoYear' AND InMonth = '$fourMonthsAgoMonth'";
$deletedRows = $pdo->exec($deleteQuery); 
foreach ($inputData as $data) {
    $routname = $data['EXECUTIVE'];
    $executiveCode = $data['ExecutiveCode'];
    $inYear = $data['Inyear'];
    $inMonth = $data['Inmonth'];
    $targetAmount = $data['TargetAmount'];
    $count = ''; // Make sure to set this value if needed

    // Check if a record with the same values exists
    $checkQuery = "SELECT COUNT(*) FROM collectionexectivetargetdata WHERE RouteCode = :RouteCode AND CollectionExective = :executivecode AND InYear = :InYear AND InMonth = :InMonth";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->bindParam(":RouteCode", $routname);
    $checkStmt->bindParam(":executivecode", $executiveCode);
    $checkStmt->bindParam(":InYear", $inYear);
    $checkStmt->bindParam(":InMonth", $inMonth);
    $checkStmt->execute();
    $rowCount = $checkStmt->fetchColumn();

    if ($rowCount == 0) {
        // No record with the same values exists, perform insert
        $insertQuery = "INSERT INTO collectionexectivetargetdata (RouteCode, CollectionExective, InYear, InMonth, Count, Sum) 
        VALUES (:RouteCode, :executivecode, :InYear, :InMonth, :Count, :Sum)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->bindParam(":RouteCode", $routname);
        $insertStmt->bindParam(":executivecode", $executiveCode);
        $insertStmt->bindParam(":InYear", $inYear);
        $insertStmt->bindParam(":InMonth", $inMonth);
        $insertStmt->bindParam(":Count", $count);
        $insertStmt->bindParam(":Sum", $targetAmount);

        $result = $insertStmt->execute();
        
        if (!$result) {
            // Delete existing records with the same InMonth value
            $deleteQuery = "DELETE FROM collectionexectivetargetdata WHERE InMonth = :InMonth";
            $deleteStmt = $pdo->prepare($deleteQuery);
            $deleteStmt->bindParam(":InMonth", $inMonth);
            $deleteStmt->execute();

            $response = 'error';
            break; // Exit the loop if an error occurs
        }
        
        $currentMonthName = date('F');
        $msg = 'Your '.$currentMonthName.' Collection Targets Ready!';
        $checkExcodeQuery = "SELECT COUNT(*) FROM notifications WHERE detail = ? AND excode = ?";
        $stmt = $pdo->prepare($checkExcodeQuery);
        $stmt->execute([$msg, $executiveCode]);
        $excodeExists = $stmt->fetchColumn() > 0;

        if (!$excodeExists) {
            $sql = "INSERT INTO `notifications` (`severity`, `summary`, `detail`, `role`, `excode`, `status`, `created_at`, `displayed`) VALUES (:severity, :summary, :detail, :role, :executiveCode, '1', NOW(), '0')";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':severity', 'success');
            $stmt->bindValue(':summary', 'Alert');
            $stmt->bindValue(':detail', $msg);
            $stmt->bindValue(':role', 'all');
            $stmt->bindValue(':executiveCode', $executiveCode);
            if ($stmt->execute()) {
                $query = "SELECT `Master_Cod` FROM `executivecodes` WHERE `Sub_Code` = '$executiveCode'";
                $stmt = $pdo->prepare($query);
                $stmt->execute();

                // Fetch the result
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result !== false) {
                    // The query was successful and returned a result
                    $masterCode = $result['Master_Cod'];
                    $currentMonthName = date('F');
                    $msg = 'Your '.$currentMonthName.' Collection Targets Ready!';
                    $checkExcodeQuery = "SELECT COUNT(*) FROM notifications WHERE detail = ? AND excode = ?";
                    $stmt = $pdo->prepare($checkExcodeQuery);
                    $stmt->execute([$msg, $masterCode]);
                    $excodeExists = $stmt->fetchColumn() > 0;

                    if (!$excodeExists) {
                        $sql = "INSERT INTO `notifications` (`severity`, `summary`, `detail`, `role`, `excode`, `status`, `created_at`, `displayed`) VALUES (:severity, :summary, :detail, :role, :executiveCode, '1', NOW(), '0')";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindValue(':severity', 'success');
                        $stmt->bindValue(':summary', 'Alert');
                        $stmt->bindValue(':detail', $msg);
                        $stmt->bindValue(':role', 'all');
                        $stmt->bindValue(':executiveCode', $masterCode);
                        if ($stmt->execute()) {
                            $query = "SELECT `Coordinator` FROM `executivecodes` WHERE `Sub_Code` = '$executiveCode'";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute();

                            // Fetch the result
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($result !== false) {
                                $Coordinator = $result['Coordinator'];
                                $currentMonthName = date('F');
                                $msg = 'Your '.$currentMonthName.' Collection Targets Ready!';
                                $checkExcodeQuery = "SELECT COUNT(*) FROM notifications WHERE detail = ? AND excode = ?";
                                $stmt = $pdo->prepare($checkExcodeQuery);
                                $stmt->execute([$msg, $Coordinator]);
                                $excodeExists = $stmt->fetchColumn() > 0;
                                if (!$excodeExists) {
                                    $sql = "INSERT INTO `notifications` (`severity`, `summary`, `detail`, `role`, `excode`, `status`, `created_at`, `displayed`) VALUES (:severity, :summary, :detail, :role, :executiveCode, '1', NOW(), '0')";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->bindValue(':severity', 'success');
                                    $stmt->bindValue(':summary', 'Alert');
                                    $stmt->bindValue(':detail', $msg);
                                    $stmt->bindValue(':role', 'all');
                                    $stmt->bindValue(':executiveCode', $Coordinator);
                                    if ($stmt->execute()) {
                                        $query = "SELECT `Mangers` FROM `executivecodes` WHERE `Sub_Code` = '$executiveCode'";
                                        $stmt = $pdo->prepare($query);
                                        $stmt->execute();

                                        // Fetch the result
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                        if ($result !== false) {
                                            $Mangers = $result['Mangers'];
                                            $currentMonthName = date('F');
                                            $msg = 'Your '.$currentMonthName.' Collection Targets Ready!';
                                            $checkExcodeQuery = "SELECT COUNT(*) FROM notifications WHERE detail = ? AND excode = ?";
                                            $stmt = $pdo->prepare($checkExcodeQuery);
                                            $stmt->execute([$msg, $Mangers]);
                                            $excodeExists = $stmt->fetchColumn() > 0;
                                            if (!$excodeExists) {
                                                $sql = "INSERT INTO `notifications` (`severity`, `summary`, `detail`, `role`, `excode`, `status`, `created_at`, `displayed`) VALUES (:severity, :summary, :detail, :role, :executiveCode, '1', NOW(), '0')";
                                                $stmt = $pdo->prepare($sql);
                                                $stmt->bindValue(':severity', 'success');
                                                $stmt->bindValue(':summary', 'Alert');
                                                $stmt->bindValue(':detail', $msg);
                                                $stmt->bindValue(':role', 'all');
                                                $stmt->bindValue(':executiveCode', $Mangers);
                                                $stmt->execute();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
      
    } else {
        $response = 'exited';
    }
}

// Echo the response
echo $response;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
   
}
?>
