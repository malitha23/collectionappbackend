<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../db-config.php');


// Check if data was received as a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON data from the request body
    $jsonData = file_get_contents('php://input');
    
    // Decode the JSON data into a PHP associative array
    $data = json_decode($jsonData, true);
   
    if ($data === null) {
        $response['status'] = 'error';
        $response['message'] = 'Error decoding JSON data.';
    } else {
        
     foreach ($data as $row) {
        $COORDINATOR = $row["COORDINATOR"];
        $ExecutiveName = $row["ExecutiveName"];
        $ExecutiveCode = $row["ExecutiveCode"];
      // Assuming $row is the associative array derived from JSON data

$Data1 = isset($row["Data1"]) ? $row["Data1"] : 0; // Set to 0 if empty or not set
$Data2 = isset($row["Data2"]) ? $row["Data2"] : 0; // Set to 0 if empty or not set
$Data3 = isset($row["Data3"]) ? $row["Data3"] : 0; // Set to 0 if empty or not set
$Data4 = isset($row["Data4"]) ? $row["Data4"] : 0; // Set to 0 if empty or not set
$Data5 = isset($row["Data5"]) ? $row["Data5"] : 0; // Set to 0 if empty or not set
$Data6 = isset($row["Data6"]) ? $row["Data6"] : 0; // Set to 0 if empty or not set

// Check for non-numeric values and handle accordingly
if (!is_numeric($Data1)) {
    $Data1 = 0; // Set to 0 if not a number
}
if (!is_numeric($Data2)) {
    $Data2 = 0; // Set to 0 if not a number
}
if (!is_numeric($Data3)) {
    $Data3 = 0; // Set to 0 if not a number
}
if (!is_numeric($Data4)) {
    $Data4 = 0; // Set to 0 if not a number
}
if (!is_numeric($Data5)) {
    $Data5 = 0; // Set to 0 if not a number
}
if (!is_numeric($Data6)) {
    $Data6 = 0; // Set to 0 if not a number
}

// Proceed with your database operations using these validated and sanitized variables

        

        $sql = "SELECT COUNT(*) AS count FROM ageingreporttargets WHERE
        COORDINATOR = '$COORDINATOR' AND
        ExecutiveName = '$ExecutiveName' AND
        ExecutiveCode = '$ExecutiveCode'";

        // Prepare and execute the query
        $stmt = $pdo->query($sql);
        // Fetch the count
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'];
       
        // If count is greater than 0, update the record
        if ($count > 0) {
            // If a record exists, perform an UPDATE
            $sqlUpdate = "UPDATE ageingreporttargets SET
                          COORDINATOR = :NewCOORDINATOR,
                          ExecutiveName = :NewExecutiveName,
                          ExecutiveCode = :NewExecutiveCode,
                          0TO31 = :NewData1,
                          31TO60 = :NewData2,
                          61TO90 = :NewData3,
                          91TO120 = :NewData4,
                          OVER120 = :NewData5,
                          TOTOUTS = :NewData6
                          WHERE
                          COORDINATOR = :COORDINATOR AND
                          ExecutiveName = :ExecutiveName AND
                          ExecutiveCode = :ExecutiveCode";

            // Prepare the UPDATE query
            $stmtUpdate = $pdo->prepare($sqlUpdate);

            // Bind parameters for the UPDATE
            $stmtUpdate->bindParam(':COORDINATOR', $COORDINATOR, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':ExecutiveName', $ExecutiveName, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':ExecutiveCode', $ExecutiveCode, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewCOORDINATOR', $COORDINATOR, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewExecutiveName', $ExecutiveName, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewExecutiveCode', $ExecutiveCode, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewData1', $Data1, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewData2', $Data2, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewData3', $Data3, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewData4', $Data4, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewData5', $Data5, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':NewData6', $Data6, PDO::PARAM_STR);

            // Execute the UPDATE query
            $stmtUpdate->execute();
        } else {
            // If no record exists, perform an INSERT
            $sqlInsert = "INSERT INTO ageingreporttargets 
                          (COORDINATOR, ExecutiveName, ExecutiveCode, 0TO31, 31TO60, 61TO90, 91TO120, OVER120, TOTOUTS)
                          VALUES
                          (:COORDINATOR, :ExecutiveName, :ExecutiveCode, :Data1, :Data2, :Data3, :Data4, :Data5, :Data6)";

            // Prepare the INSERT query
            $stmtInsert = $pdo->prepare($sqlInsert);

            // Bind parameters for the INSERT
            $stmtInsert->bindParam(':COORDINATOR', $COORDINATOR, PDO::PARAM_STR);
            $stmtInsert->bindParam(':ExecutiveName', $ExecutiveName, PDO::PARAM_STR);
            $stmtInsert->bindParam(':ExecutiveCode', $ExecutiveCode, PDO::PARAM_STR);
            $stmtInsert->bindParam(':Data1', $Data1);
            $stmtInsert->bindParam(':Data2', $Data2, PDO::PARAM_STR);
            $stmtInsert->bindParam(':Data3', $Data3, PDO::PARAM_STR);
            $stmtInsert->bindParam(':Data4', $Data4, PDO::PARAM_STR);
            $stmtInsert->bindParam(':Data5', $Data5, PDO::PARAM_STR);
            $stmtInsert->bindParam(':Data6', $Data6, PDO::PARAM_STR);

            // Execute the INSERT query
            $stmtInsert->execute();
        }
        
     }
     

$response['status'] = 'success';
$response['message'] = 'Data updated successfully!';
    }
    
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method. Expected POST.'; 
}
// Send the JSON response

 header('Content-Type: application/json');
 echo json_encode($response); 
?>
