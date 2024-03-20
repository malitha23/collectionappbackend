<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../db-config.php');

$sql = "SELECT `insertTime`, `descriptions`, `ComplainantOwnerName`, `ComplainantOwnerPno`, `complaint`, `subcomplaint`, SUBSTRING_INDEX(`customerCode`, '-', 1) AS `ccode`, SUBSTRING_INDEX(`customerCode`, '-', -1) AS `bcode`, `excode`
        FROM `remarksdata`
        WHERE `status` = '0' AND `sqlInserted` = '0' ";

// Prepare the statement
$stmt = $pdo->prepare($sql);

// Execute the statement
$stmt->execute();

// Fetch all rows as an associative array
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($data) {
    $formattedData = [];
    foreach ($data as $row) {
        $insertTime = $row["insertTime"];
        $complaint = $row["complaint"];
        $subcomplaint = $row["subcomplaint"];
        $ccode = $row["ccode"];
        $bcode = $row["bcode"];
        $excode = $row["excode"];
        $descriptions = $row["descriptions"];
        $ComplainantOwnerName = $row["ComplainantOwnerName"];
        $ComplainantOwnerPno = $row["ComplainantOwnerPno"];
        
        if($complaint == 'Water Complaint'){
            $newcomplaint = 'Watercomplain';
        }else if($complaint == 'Cooler Complaint'){
            $newcomplaint = 'Coolercomplain';
        }else if($complaint == 'Product Complaint'){
            $newcomplaint = 'Productcomplain';
        }else if($complaint == 'Collection Complaint'){
            $newcomplaint = 'Collectioncomplain';
        }else{
            $newcomplaint = '';
        }
        // Create an associative array for each row
        $formattedRow = [
            "insertTime" => $insertTime,
            "complaint" => $newcomplaint,
            "subcomplaint" => $subcomplaint,
            "ccode" => $ccode,
            "bcode" => $bcode,
            "excode" => $excode,
            "descriptions" => $descriptions,
            "ComplainantOwnerName" => $ComplainantOwnerName,
            "ComplainantOwnerPno" => $ComplainantOwnerPno
        ];

        // Add the formatted row to the result array
        $formattedData[] = $formattedRow;
    }

    // Convert the result array to JSON
    $jsonResponse = json_encode($formattedData);

    // Set the response content type
    header('Content-Type: application/json');

    // Output the JSON response
    echo $jsonResponse;
}
?>
