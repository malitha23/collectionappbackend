<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../db-config.php');

$sql = "SELECT managername, managercode, targetPercentage FROM commitmenttargettoteam";

    // Prepare and execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Fetch the data as an associative array
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create an array to hold the response data
    $response = array();

    // Add the fetched data to the response array
    foreach ($result as $row) {
        $response[] = array(
            'managername' => $row['managername'],
            'managercode' => $row['managercode'],
            'targetPercentage' => $row['targetPercentage']
        );
    }

    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
   
    $pdo = null;
?>