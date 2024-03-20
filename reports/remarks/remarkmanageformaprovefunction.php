<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../../db-config.php');

// Get the request payload
$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

// Extract the login data

// $date = '2023-09-18';
// $complaint = 'water';
// $customerCode = 'A0001-002';

$date = $requestPayload['date'];
$complaint = $requestPayload['complaint'];
$customerCode = $requestPayload['customerCode'];
$report = $requestPayload['report'];
$insertDate = $requestPayload['insertDate'];
$datePortion = substr($insertDate, 0, 10);

if($report == 'true'){
    $newcomplaint  = $complaint;
}else{
    if($complaint == 'water'){
        $newcomplaint = 'Water Complaint';
    }else if($complaint == 'cooler'){
        $newcomplaint = 'Cooler Complaint';
    }else if($complaint == 'product'){
        $newcomplaint = 'Product Complaint';
    }else if($complaint == 'collection'){
        $newcomplaint = 'Collection Complaint';
    }
}


   // Construct the UPDATE query
   $sql = "UPDATE remarksdata 
   SET status = '0', insertTime = :insertDate, remarksdate = :remarksdate
   WHERE remarksdate = :date AND complaint = :complaint AND customerCode = :customerCode";
   
// Prepare the SQL statement
$stmt = $pdo->prepare($sql);

// Bind parameters
$stmt->bindParam(':remarksdate', $datePortion);
$stmt->bindParam(':insertDate', $insertDate);
$stmt->bindParam(':date', $date);
$stmt->bindParam(':complaint', $newcomplaint);
$stmt->bindParam(':customerCode', $customerCode);

// Execute the query
$stmt->execute();

// Check the number of affected rows
$rowCount = $stmt->rowCount();

if ($rowCount > 0) {
echo json_encode(["status" => true, "message" => "Records updated successfully"]);
} else {
echo json_encode(["status" => false, "error" => "No matching records found"]);
}



?>