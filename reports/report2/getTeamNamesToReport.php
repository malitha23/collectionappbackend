<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../../db-config.php');

$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

$dayrange = $requestPayload['dayrange'];
$executiveCode = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];

error_reporting(E_ALL);
ini_set('display_errors', 1);
if($role == "Head"){
    $query =   "SELECT DISTINCT `Manger_Name` AS name , `Mangers` AS code FROM executivecodes";
}else if($role == "Manager"){
    $query =   "SELECT DISTINCT `Coordinator_Name` AS name ,`Coordinator` AS code FROM executivecodes WHERE `Mangers` = '$executiveCode'";
}else if($role == "Coordinator"){
    $query =   "SELECT DISTINCT `Master_exe_Name` AS name ,`Master_Cod` AS code FROM executivecodes WHERE `Coordinator` = '$executiveCode'";
}else if($role == "Master_Code"){
    $query =   "SELECT DISTINCT `Master_exe_Name` AS name ,`Master_Cod` AS code FROM executivecodes WHERE `Master_Cod` = '$executiveCode'";
}

// Prepare the SQL statement
$sql = $query;
$stmt = $pdo->prepare($sql);
$stmt->execute();
// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'name' => $row['name'],
        'codes' => $row['code']
    );
}

// Return the response
echo json_encode($options);
?>
