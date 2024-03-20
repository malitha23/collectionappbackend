<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
// $role = $data['role'];
// $executiveCode = $data['code'];

// Prepare the SQL statement
$sql = "SELECT DISTINCT Manger_Name, Mangers FROM executivecodes";
$stmt = $pdo->prepare($sql);
$stmt->execute();
// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'ManagerName' => $row['Manger_Name'],
        'Manager' => $row['Mangers']
    );
}

// Return the response
echo json_encode($options);
?>
