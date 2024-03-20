<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once('../db-config.php');

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
$dayrange = $data['dayrange'];
$month = $data['month'];
$year = $data['year'];
// $executiveCode = $data['code'];


// Prepare the SQL statement
// $sql = "SELECT ec.Manger_Name, ec.Mangers, COALESCE(SUM(ctd.value), 0) AS firstTagetAchieved 
// FROM executivecodes AS ec 
// LEFT JOIN collectiondata AS ctd ON ctd.excode = ec.Sub_Code 
// WHERE (MONTH(ctd.collectiondate) = :month 
// AND YEAR(ctd.collectiondate) = :year 
// AND DAY(ctd.collectiondate) BETWEEN 1 AND :dayrange) OR ctd.collectiondate IS NULL 
// GROUP BY ec.Mangers";
 $sql = "SELECT ec.Manger_Name, ec.Mangers, COALESCE(SUM(ctd.TOTOUTS), 0) AS firstTagetAchieved FROM executivecodes AS ec LEFT JOIN ageingreport AS ctd ON ctd.ExecutiveCode = ec.Sub_Code GROUP BY ec.Mangers";

$stmt = $pdo->prepare($sql);
// $stmt->bindParam(':year', $year, PDO::PARAM_INT);
// $stmt->bindParam(':month', $month, PDO::PARAM_INT);
// $stmt->bindParam(':dayrange', $dayrange, PDO::PARAM_INT);
$stmt->execute();


// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'ManagerName' => $row['Manger_Name'],
        'Manager' => $row['Mangers'],
        'firstTagetAchieved' => $row['firstTagetAchieved']
    );
}

// Return the response
echo json_encode($options);
?>