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
        $Data1 = $row["Data1"];
        $Data2 = $row["Data2"];
        $Data3 = $row["Data3"];
        $Data4 = $row["Data4"];
        $Data5 = $row["Data5"];
        $Data6 = $row["Data6"];

        $sql = "SELECT COUNT(*) AS count FROM outstnading WHERE
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
            $sqlUpdate = "UPDATE outstnading SET
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
            $sqlInsert = "INSERT INTO outstnading 
                          (COORDINATOR, ExecutiveName, ExecutiveCode, 0TO31, 31TO60, 61TO90, 91TO120, OVER120, TOTOUTS)
                          VALUES
                          (:COORDINATOR, :ExecutiveName, :ExecutiveCode, :Data1, :Data2, :Data3, :Data4, :Data5, :Data6)";

            // Prepare the INSERT query
            $stmtInsert = $pdo->prepare($sqlInsert);

            // Bind parameters for the INSERT
            $stmtInsert->bindParam(':COORDINATOR', $COORDINATOR, PDO::PARAM_STR);
            $stmtInsert->bindParam(':ExecutiveName', $ExecutiveName, PDO::PARAM_STR);
            $stmtInsert->bindParam(':ExecutiveCode', $ExecutiveCode, PDO::PARAM_STR);
            $stmtInsert->bindParam(':Data1', $Data1, PDO::PARAM_STR);
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
