<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
$role = $data['role'];
$executiveCode = $data['code'];

// Prepare the SQL statement
$sql = "SELECT t1.`Master_Cod`, t1.`Master_exe_Name`
FROM `executivecodes` t1
JOIN (
  SELECT `Master_Cod`, MIN(`id`) AS min_id
  FROM `executivecodes`
  WHERE `Coordinator` = '$executiveCode'
  GROUP BY `Master_Cod`
  HAVING COUNT(*) > 1
) t2 ON t1.`Master_Cod` = t2.`Master_Cod` AND t1.`id` = t2.min_id
WHERE t1.`Coordinator` = '$executiveCode'

UNION

SELECT t3.`Master_Cod`, t3.`Master_exe_Name`
FROM `executivecodes` t3
WHERE t3.`Coordinator` = '$executiveCode'
  AND t3.`Master_Cod` NOT IN (
    SELECT `Master_Cod`
    FROM `executivecodes`
    WHERE `Coordinator` = '$executiveCode'
    GROUP BY `Master_Cod`
    HAVING COUNT(*) > 1
  )";


$stmt = $pdo->prepare($sql);
$stmt->execute();

// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'masterExeName' => $row['Master_exe_Name'],
        'masterCod' => $row['Master_Cod']
    );
}

// Return the response
echo json_encode($options);
?>
