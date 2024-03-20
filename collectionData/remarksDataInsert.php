<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Check if the table exists, and create it if it doesn't
$checkTableQuery = "SHOW TABLES LIKE 'remarksdata'";
$tableExists = $pdo->query($checkTableQuery)->rowCount() > 0;

if (!$tableExists) {
    // Create the table
    $createTableQuery = "CREATE TABLE remarksdata (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        remarksdate DATE,
        complaint VARCHAR(50),
        subcomplaint VARCHAR(150),
        customerCode VARCHAR(20),
        descriptions VARCHAR(250),
        ComplainantOwnerName VARCHAR(20),
        ComplainantOwnerPno VARCHAR(10),
        excode VARCHAR(10),
        insertExcode VARCHAR(10),
        insertedRole VARCHAR(20),
        status INT(2),
        sqlInserted (2),
        insertTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->query($createTableQuery);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the data from the request
    $data = json_decode(file_get_contents("php://input"));

    if ($data === null) {
        // Error in decoding JSON
        $response = array("success" => false, "message" => "Invalid JSON data");
        echo json_encode($response);
        exit;
    }

    // Access collectionData
    $collectionData = $data->collectionData;

    // Insert collectionData into the database if needed
    // For example:
    // $excode = $collectionData->excode;
    // $insertExcode = $collectionData->insertExcode;
    // $insertedRole = $collectionData->insertedRole;
    // Perform the INSERT SQL query for collectionData here if needed

    // Access complaintRows
    $complaintRows = $data->complaintRows;

    $success = true; // Initialize a success flag

    foreach ($complaintRows as $row) {
        // Ensure that $row is an object before accessing properties
        if (is_object($row)) {
            $complaint = $row->complaint;
            if($complaint == 'water'){
                $newcomplaint = 'Water Complaint';
            }else if($complaint == 'cooler'){
                $newcomplaint = 'Cooler Complaint';
            }else if($complaint == 'product'){
                $newcomplaint = 'Product Complaint';
            }else if($complaint == 'collection'){
                $newcomplaint = 'Collection Complaint';
            }

            $customerCode = $row->customerCode;
            $scomplaint = $row->subcomplaint;
            $subcomplaint = $scomplaint . " (CollectionApp)";
            $date = $row->date;
            $subcategory = $row->subcategory;
            $complaintnername = $row->complaintnername;
            $complaintnerpno = $row->complaintnerpno;
            if($complaintnerpno == ""){
                $complaintnerpno = 0;
            }else{
               $complaintnerpno = $row->complaintnerpno; 
            }
            $excode = $collectionData->excode; // You can access collectionData properties if needed
            $insertExcode = $collectionData->insertExcode; // You can access collectionData properties if needed
            $insertedRole = $collectionData->insertedRole; // You can access collectionData properties if needed

            // Perform the INSERT SQL query here using prepared statements
            $stmt = $pdo->prepare("INSERT INTO remarksdata (remarksdate, complaint, subcomplaint, customerCode, descriptions, ComplainantOwnerName, ComplainantOwnerPno, excode, insertExcode, insertedRole, status, sqlInserted) 
                                   VALUES (:remarksdate, :complaint, :subcomplaint, :customerCode, :description, :ComplainantOwnerName, :ComplainantOwnerPno, :excode, :insertExcode, :insertedRole, -1, 0)");
            
            $stmt->bindParam(':remarksdate', $date);
            $stmt->bindParam(':complaint', $newcomplaint);
            $stmt->bindParam(':subcomplaint', $subcomplaint);
            $stmt->bindParam(':customerCode', $customerCode);
            $stmt->bindParam(':description', $subcategory);
            $stmt->bindParam(':ComplainantOwnerName', $complaintnername);
            $stmt->bindParam(':ComplainantOwnerPno', $complaintnerpno);
            $stmt->bindParam(':excode', $excode);
            $stmt->bindParam(':insertExcode', $insertExcode);
            $stmt->bindParam(':insertedRole', $insertedRole);

            if (!$stmt->execute()) {
                // Set the success flag to false if the insertion fails
                $success = false;
            }
        }
    }
    
    if ($success) {
        $response = array("success" => true, "message" => "Insertion successful");
    } else {
        $response = array("success" => false, "message" => "Insertion failed");
    }

    echo json_encode($response);
}
?>
