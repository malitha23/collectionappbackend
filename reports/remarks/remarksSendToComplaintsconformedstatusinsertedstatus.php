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
    $insertTime = $complainData['insertTime'];
    $cCode = $complainData['ccode'];
    $bCode = $complainData['bcode'];
    $fullcode = $cCode . '-' . $bCode; // Concatenate with a hyphen
    $complaint = $complainData['complaint'];
    $subcomplaint = $complainData['subcomplaint'];
    $excode = $complainData['excode'];

    if($complaint == 'Watercomplain'){
        $newcomplaint = 'Water Complaint';
    }else if($complaint == 'Coolercomplain'){
        $newcomplaint = 'Cooler Complaint';
    }else if($complaint == 'Productcomplain'){
        $newcomplaint = 'Product Complaint';
    }else if($complaint == 'Collectioncomplain'){
        $newcomplaint = 'Collection Complaint';
    }
   
    $sql = "SELECT id FROM remarksdata WHERE insertTime = :insertTime AND customerCode = :customerCode AND complaint = :complaint AND subcomplaint = :subcomplaint AND excode = :excode";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':customerCode', $fullcode, PDO::PARAM_STR);
    $stmt->bindParam(':insertTime', $insertTime, PDO::PARAM_STR);
    $stmt->bindParam(':complaint', $newcomplaint, PDO::PARAM_STR);
    $stmt->bindParam(':subcomplaint', $subcomplaint, PDO::PARAM_STR);
    $stmt->bindParam(':excode', $excode, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the 'id' value
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $id = $result['id'];
        $sql = "UPDATE remarksdata SET sqlInserted = '1' WHERE id = :id";
        $stmt = $pdo->prepare($sql);
    
        // Bind parameters
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        if ($stmt->execute()) {
        $response = ["message" => $id];
            echo json_encode($response);
        }
    }
}

 ?>