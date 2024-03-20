<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../../db-config.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

$dayrange = $requestPayload['dayrange'];
$executiveCode = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];




if($role == "Head"){
    $query =   "SELECT ec.Manger_Name AS names ,ec.Mangers AS codes, COALESCE(SUM(ctd.value), 0) AS firstTagetAchieved 
    FROM executivecodes AS ec 
    LEFT JOIN collectiondata AS ctd ON ctd.excode = ec.Sub_Code 
    WHERE (MONTH(ctd.collectiondate) = MONTH(CURRENT_DATE()) 
    AND YEAR(ctd.collectiondate) = YEAR(CURRENT_DATE()) 
    AND DAY(ctd.collectiondate) BETWEEN 0 AND $dayrange) OR ctd.collectiondate IS NULL 
    GROUP BY ec.Mangers";
}else if($role == "Manager"){
    $query =   "SELECT ec.Mangers, ec.Manger_Name, ec.Coordinator_Name AS names, ec.Coordinator AS codes, COALESCE(SUM(ctd.value), 0) AS firstTagetAchieved
    FROM (
      SELECT *
      FROM executivecodes
      WHERE Mangers = '$executiveCode'
    ) AS ec
    LEFT JOIN collectiondata AS ctd ON ctd.excode = ec.Sub_Code
    WHERE (MONTH(ctd.collectiondate) = MONTH(CURRENT_DATE()) AND YEAR(ctd.collectiondate) = YEAR(CURRENT_DATE()) AND DAY(ctd.collectiondate) BETWEEN 0 AND $dayrange) OR ctd.collectiondate IS NULL
    GROUP BY ec.Coordinator_Name;
    ";
}else if($role == "Coordinator"){
    $query =   "SELECT ec.Coordinator, ec.Coordinator_Name, ec.Master_exe_Name AS names, ec.Master_Cod AS codes, COALESCE(SUM(ctd.value), 0) AS firstTagetAchieved FROM ( SELECT * FROM executivecodes WHERE Coordinator = '$executiveCode' ) AS ec LEFT JOIN collectiondata AS ctd ON ctd.excode = ec.Sub_Code WHERE (MONTH(ctd.collectiondate) = MONTH(CURRENT_DATE()) AND YEAR(ctd.collectiondate) = YEAR(CURRENT_DATE()) AND DAY(ctd.collectiondate) BETWEEN 0 AND $dayrange) OR ctd.collectiondate IS NULL GROUP BY ec.Master_Cod";
}else if($role == "Master_Code"){
    $query =   "SELECT ec.Master_Cod, ec.Master_exe_Name, ec.Master_exe_Name AS names, ec.Sub_Code AS codes, COALESCE(SUM(ctd.value), 0) AS firstTagetAchieved FROM ( SELECT * FROM executivecodes WHERE Master_Cod = '$executiveCode' ) AS ec LEFT JOIN collectiondata AS ctd ON ctd.excode = ec.Sub_Code WHERE (MONTH(ctd.collectiondate) = MONTH(CURRENT_DATE()) AND YEAR(ctd.collectiondate) = YEAR(CURRENT_DATE()) AND DAY(ctd.collectiondate) BETWEEN 0 AND $dayrange) OR ctd.collectiondate IS NULL GROUP BY ec.Sub_Code";
}

$sql = $query;

$stmt = $pdo->prepare($sql);
$stmt->execute();

// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    
    $options[] = array(
        'names' => $row['names'],
        'codes' => $row['codes'],
        'firstTagetAchieved' => $row['firstTagetAchieved']
    );
}

// Return the response
echo json_encode($options);
?>
