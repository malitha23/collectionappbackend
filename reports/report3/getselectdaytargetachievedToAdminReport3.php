<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../db-config.php');

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
$dayrange = $data['dayrange'];
$month = $data['month'];
$year = $data['year'];
// $executiveCode = $data['code'];
$tableName = 'targetdayranges';

// Query to select the 'day' value from the targetDayRanges table
$sqlDay = "SELECT day FROM $tableName LIMIT 1";

// Execute the query to get the 'day' value
$dayResult = $pdo->query($sqlDay);
$firstDayRow = $dayResult->fetch(PDO::FETCH_ASSOC);
$firstDay = $firstDayRow['day'];

// Prepare the SQL statement
$sql = "SELECT ec.Manger_Name, ec.Mangers, COALESCE(SUM(ctd.value), 0) AS selectdayTagetAchieved 
FROM executivecodes AS ec 
LEFT JOIN collectiondata AS ctd ON ctd.excode = ec.Sub_Code 
WHERE (MONTH(ctd.collectiondate) = '$month' 
AND YEAR(ctd.collectiondate) = '$year' 
AND DAY(ctd.collectiondate)= '$dayrange') OR ctd.collectiondate IS NULL 
GROUP BY ec.Mangers";

$stmt = $pdo->prepare($sql);
$stmt->execute();

// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'ManagerName' => $row['Manger_Name'],
        'Manager' => $row['Mangers'],
        'selectdayTagetAchieved' => $row['selectdayTagetAchieved']
    );
}

// Return the response
echo json_encode($options);
?>