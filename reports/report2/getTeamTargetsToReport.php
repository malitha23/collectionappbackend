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


$selectRole = '';
if($role == "Head"){
    $query =   "SELECT ec.Manger_Name AS names, ec.Mangers AS codes, SUM(ctd.Sum) AS targets 
    FROM executivecodes AS ec 
    INNER JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
    WHERE ctd.InYear = YEAR(CURRENT_DATE()) AND ctd.InMonth = MONTH(CURRENT_DATE()) AND ec.Mangers != 'PO001' GROUP BY ec.Mangers";
    $selectRole = 'Manager';
}else if($role == "Manager"){
    $query =   "SELECT ec.Coordinator_Name AS names, ec.Coordinator AS codes, SUM(ctd.Sum) AS targets 
    FROM executivecodes AS ec 
    INNER JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
    WHERE ctd.InYear = YEAR(CURRENT_DATE()) AND ctd.InMonth = MONTH(CURRENT_DATE()) AND ec.Mangers = '$executiveCode'
    GROUP BY ec.Coordinator";
    $selectRole = 'Coordinator';
}else if($role == "Coordinator"){
    $query =   "SELECT ec.`Master_exe_Name` AS names, ec.`Master_Cod` AS codes, SUM(ctd.Sum) AS targets 
    FROM executivecodes AS ec 
    INNER JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
    WHERE ctd.InYear = YEAR(CURRENT_DATE()) AND ctd.InMonth = MONTH(CURRENT_DATE()) AND ec.Coordinator='$executiveCode'
    GROUP BY ec.Master_Cod";
    $selectRole = 'Master_Code';
}else{
    $query =   "SELECT ec.`Master_exe_Name` AS names, ec.Sub_Code AS codes, SUM(ctd.Sum) AS targets FROM executivecodes AS ec INNER JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code WHERE ctd.InYear = YEAR(CURRENT_DATE()) AND ctd.InMonth = MONTH(CURRENT_DATE()) AND ec.Sub_Code='$executiveCode' GROUP BY ec.Master_exe_Name";
    $selectRole = 'Sub_Code';
}

// Prepare the SQL statement
$sql = $query ;
$stmt = $pdo->prepare($sql);
$stmt->execute();
// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'names' => $row['names'],
        'codes' => $row['codes'],
        'role' => $selectRole,
        'targets' => $row['targets']
    );
}

// Return the response
echo json_encode($options);
?>
