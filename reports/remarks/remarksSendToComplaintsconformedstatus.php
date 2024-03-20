<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../db-config.php');

// Read the raw POST data as JSON
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);

if ($inputData === null) {
    // JSON parsing failed
    echo json_encode(["error" => "Invalid JSON data"]);
    exit;
}

// Process the received JSON data
foreach ($inputData as $complainData) {
    // Access the fields as needed
    $complaindatetime = $complainData['Complaindatetime'];
    $cCode = $complainData['CustomerCode'];
    $bCode = $complainData['BranchCode'];
    $fullcode = $cCode . '-' . $bCode; // Concatenate with a hyphen
    $closedDateTime = $complainData['ClosedDateTime'];
    $ComplainNumber = $complainData['ComplainNumber'];
    $complaint = $complainData['ComplainCategory'];
    $subcomplaint = $complainData['ComplainSCategory'];
    if($complaint == 'Watercomplain'){
        $newcomplaint = 'Water Complaint';
    }else if($complaint == 'Coolercomplain'){
        $newcomplaint = 'Cooler Complaint';
    }else if($complaint == 'Productcomplain'){
        $newcomplaint = 'Product Complaint';
    }else if($complaint == 'Collectioncomplain'){
        $newcomplaint = 'Collection Complaint';
    }

    // Update 'remarksdata' table
    $sql = "UPDATE remarksdata SET status = '1' WHERE customerCode = :customerCode AND insertTime = :insertTime AND complaint = :complaint AND subcomplaint = :subcomplaint";
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':customerCode', $fullcode, PDO::PARAM_STR);
    $stmt->bindParam(':insertTime', $complaindatetime, PDO::PARAM_STR);
    $stmt->bindParam(':complaint', $newcomplaint, PDO::PARAM_STR);
    $stmt->bindParam(':subcomplaint', $subcomplaint, PDO::PARAM_STR);
    // Execute the update
    if ($stmt->execute()) {
        // Select 'id' from 'remarksdata'
        $sql = "SELECT id FROM remarksdata WHERE insertTime = :insertTime AND customerCode = :customerCode AND complaint = :complaint AND subcomplaint = :subcomplaint";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':customerCode', $fullcode, PDO::PARAM_STR);
        $stmt->bindParam(':insertTime', $complaindatetime, PDO::PARAM_STR);
        $stmt->bindParam(':complaint', $newcomplaint, PDO::PARAM_STR);
        $stmt->bindParam(':subcomplaint', $subcomplaint, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the 'id' value
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $id = $result['id'];

            // Check if a record with the same 'id' exists
$checkSql = "SELECT id FROM remarksclosedresults WHERE id = :id";
$checkStmt = $pdo->prepare($checkSql);
$checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
$checkStmt->execute();
$existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existingRecord) {
    // Record with the same 'id' exists, so update it
    $updateSql = "UPDATE remarksclosedresults 
                  SET complaintNo = :complaintNo, customerCode = :customerCode, branchCode = :branchCode, closedDate = :closedDate
                  WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $updateStmt->bindParam(':complaintNo', $ComplainNumber, PDO::PARAM_STR);
    $updateStmt->bindParam(':customerCode', $cCode, PDO::PARAM_STR);
    $updateStmt->bindParam(':branchCode', $bCode, PDO::PARAM_STR);
    $updateStmt->bindParam(':closedDate', $closedDateTime, PDO::PARAM_STR);

    if ($updateStmt->execute()) {
        createNotificationcheck($fullcode, $complaindatetime, $pdo);
        // Record updated successfully
        echo json_encode(["success" => "Record updated successfully"]);
    } else {
        // Update failed
        echo json_encode(["error" => "Update failed"]);
    }
} else {
    // No record with the same 'id' exists, so insert a new record
    $insertSql = "INSERT INTO remarksclosedresults (id, complaintNo, customerCode, branchCode, closedDate) 
                  VALUES (:id, :complaintNo, :customerCode, :branchCode, :closedDate)";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $insertStmt->bindParam(':complaintNo', $ComplainNumber, PDO::PARAM_STR);
    $insertStmt->bindParam(':customerCode', $cCode, PDO::PARAM_STR);
    $insertStmt->bindParam(':branchCode', $bCode, PDO::PARAM_STR);
    $insertStmt->bindParam(':closedDate', $closedDateTime, PDO::PARAM_STR);

    if ($insertStmt->execute()) {
        createNotificationcheck($fullcode, $complaindatetime, $pdo, $newcomplaint, $subcomplaint);
        // Record inserted successfully
        echo json_encode(["success" => "Record inserted successfully"]);
    } else {
        // Insert failed
        echo json_encode(["error" => "Insert failed"]);
    }
}

        } else {
            // 'id' not found
            echo json_encode(["error" => "ID not found"]);
        }
    } else {
        // Update failed
        echo json_encode(["error" => "Data update failed"]);
    }
}

 function createNotificationcheck($fullcode, $complaindatetime, $pdo, $newcomplaint, $subcomplaint){

    $sql = "SELECT * FROM remarksdata WHERE insertTime = :insertTime AND customerCode = :customerCode AND complaint = :complaint AND subcomplaint = :subcomplaint";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':customerCode', $fullcode, PDO::PARAM_STR);
        $stmt->bindParam(':insertTime', $complaindatetime, PDO::PARAM_STR);
        $stmt->bindParam(':complaint', $newcomplaint, PDO::PARAM_STR);
        $stmt->bindParam(':subcomplaint', $subcomplaint, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the 'id' value
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $complaint = $result['complaint'];
            $customerCode = $result['customerCode'];
            $excode = $result['excode'];
            $insertExcode = $result['insertExcode'];
            $insertedRole = $result['insertedRole'];
        }
        
        if($insertedRole == 'Head'){

        }else if($insertedRole == 'Manager'){
            $sql = "SELECT * FROM `executivecodes` WHERE `Master_Cod` = :cod GROUP BY `Master_Cod`";

// Prepare the SQL statement
$stmt = $pdo->prepare($sql);

// Bind the value to the placeholder
$stmt->bindParam(':cod', $excode);

// Execute the query
$stmt->execute();

// Fetch the results as an associative array
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if the results array is not empty
if (!empty($results)) {
    // Access the data from the first row in the results array
    $firstRow = $results[0];

    $Master_Cod = $firstRow["Master_Cod"];  
    $msg = $customerCode . ' customer ' . $complaint . ' is solved!';
    createNotification($complaint, $customerCode, $Master_Cod, $pdo, $msg);

    $mastername = $firstRow["Master_exe_Name"];
    $Coordinator = $firstRow["Coordinator"];
    $msg = $mastername . '`s ' . $customerCode . ' customer ' . $complaint . ' is solved!';
    createNotification($complaint, $customerCode, $Coordinator, $pdo, $msg);

    $Mangers = $firstRow["Mangers"];
    $msg = $mastername . '`s ' . $customerCode . ' customer ' . $complaint . ' is solved!';
    createNotification($complaint, $customerCode, $Mangers, $pdo, $msg);
}


        }else if($insertedRole == 'Coordinator'){
            
            $msg = $customerCode.' customer '.$complaint.' is solved!';
            createNotification($complaint, $customerCode, $excode, $pdo, $msg);
            $mastername = getmasternametomsg($pdo, $excode);
            $msg = $mastername.'`s '.$customerCode.' customer '.$complaint.' is solved!';
            createNotification($complaint, $customerCode, $insertExcode, $pdo, $msg);

        }else if($insertedRole == 'Master_Code'){
            $msg = $customerCode.' customer '.$complaint.' is solved!';
            createNotification($complaint, $customerCode, $excode, $pdo, $msg);
        }

 }

 function createNotification($complaint, $customerCode, $executiveCode, $pdo, $msg){

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
    $stmt->execute(); 
        }
 }

 function getmasternametomsg($pdo, $cod){

   $sql = "SELECT `Master_exe_Name` FROM `executivecodes` WHERE `Master_Cod` = :cod";

    // Prepare the SQL statement
    $stmt = $pdo->prepare($sql);

    // Bind the value to the placeholder
    $stmt->bindParam(':cod', $cod, PDO::PARAM_STR);

    // Execute the query
    $stmt->execute();

    // Fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($result) {
    $masterExeName = $result['Master_exe_Name'];
    return $masterExeName;
   }
 }

 function getmanagerroledatatomsg($pdo, $cod){
    $sql = "SELECT * FROM `executivecodes` WHERE `Master_Cod` = :cod GROUP BY `Master_Cod`";

  // Prepare the SQL statement
   $stmt = $pdo->prepare($sql);

   // Bind the value to the placeholder
   $stmt->bindParam(':cod', $cod, PDO::PARAM_STR);

   // Execute the query
   $stmt->execute();

   // Fetch the results as an associative array
   $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
   return $result;
 }


?>
