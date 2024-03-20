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

// Assuming you have already prepared your PDO connection ($pdo)
$sql1 = "SELECT id FROM remarksdata WHERE remarksdate = :date AND complaint = :complaint AND customerCode = :customerCode";

// Prepare the SQL statement
$stmt = $pdo->prepare($sql1);

// Bind parameters
$stmt->bindParam(':date', $date);
$stmt->bindParam(':complaint', $newcomplaint);
$stmt->bindParam(':customerCode', $customerCode);

// Execute the query
$stmt->execute();

// Fetch the IDs into an array
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
// Assuming you have already prepared your PDO connection ($pdo) and have retrieved the IDs into an array called $ids

// Define the DELETE query
$sql2 = "DELETE FROM remarksclosedresults WHERE id = :id";

// Prepare the SQL statement
$stmt = $pdo->prepare($sql2);

// Loop through the $ids array and delete each row
foreach ($ids as $id) {
    // Bind the ID parameter
    $stmt->bindParam(':id', $id);

    // Execute the DELETE query
    $stmt->execute();
}

    // Construct the DELETE query
    $sql = "DELETE FROM remarksdata WHERE remarksdate = :date AND complaint = :complaint AND customerCode = :customerCode ";
    
    // Prepare the SQL statement
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':complaint', $newcomplaint);
    $stmt->bindParam(':customerCode', $customerCode);
    
    // Execute the query
    $stmt->execute();
    
    // Check the number of affected rows
    $rowCount = $stmt->rowCount();
    
    if ($rowCount > 0) {
        echo json_encode([ "status" => true , "message" => "Records deleted successfully"]);
    } else {
        // http_response_code(400);
        echo json_encode(["status" => false ,"error" => "No matching records found"]);
    }


?>