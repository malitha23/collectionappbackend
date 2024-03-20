<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../db-config.php');
    // Assuming the received data is in JSON format
    $requestData = json_decode(file_get_contents('php://input'), true);

    foreach ($requestData as $managerData) {
        // Prepare the SQL query to update each row based on the 'id' (assuming 'id' is the primary key)
        $sql = "UPDATE commitmenttargettoteam 
                SET 
                  targetPercentage = :targetPercentage
                WHERE 
                managercode = :managercode";

        // Prepare and execute the update query
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':managercode' => $managerData['managercode'],
            ':targetPercentage' => $managerData['targetPercentage'],
        ]);
    }

    // Return a success response (you can customize the response as needed)
    header('Content-Type: application/json');
    echo json_encode(['success'=> true , 'message' => 'Update successful']);
   
    $pdo = null;
?>