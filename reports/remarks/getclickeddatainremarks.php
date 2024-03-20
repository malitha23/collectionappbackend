<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../db-config.php');

// Get the request payload
$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

// Extract the login data

// $date = '2023-09-12';
// $executiveCode = 'EX0057';
// $complaint = 'water';

$date = $requestPayload['date'];
$executiveCode = $requestPayload['Executivecode'];
$complaint = $requestPayload['complaint'];

if($complaint == 'water'){
    $newcomplaint = 'Water Complaint';
}else if($complaint == 'cooler'){
    $newcomplaint = 'Cooler Complaint';
}else if($complaint == 'product'){
    $newcomplaint = 'Product Complaint';
}else if($complaint == 'collection'){
    $newcomplaint = 'Collection Complaint';
}


    $query1 = "SELECT * FROM `remarksdata` WHERE `excode` = :excode AND `complaint` = :complaint AND `remarksdate` = :date";
    $results = Master_CodefetchRemarksData($executiveCode, $date, $newcomplaint, $pdo, $query1);
    echo json_encode($results);

function Master_CodefetchRemarksData($executiveCode, $date, $complaint, $pdo, $query1) {
         
            // Query 1
            $stmt1 = $pdo->prepare($query1);
            $stmt1->bindParam(':excode', $executiveCode);
            $stmt1->bindParam(':date', $date);
            $stmt1->bindParam(':complaint', $complaint);
            $stmt1->execute();
            $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        return $result1;
}
?>