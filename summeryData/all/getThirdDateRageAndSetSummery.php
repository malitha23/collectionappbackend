<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../db-config.php');

// Get the request payload
$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

// Extract the login data
$month = $requestPayload['month'];
$executiveCode = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];

// $month = 8;
// $executiveCode = 'He001';
// $role = 'Head';
$year = date('Y');


if($role == "Head"){
     $query = "SELECT * FROM `categorymanagers` GROUP BY `category`";
     functioncallfirstadmin($query);
}else if($role == "Manager"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = :executiveCode ";
  functioncallfirst($query);
}else if($role == "Coordinator"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = (
      SELECT DISTINCT `Mangers`
      FROM `executivecodes`
      WHERE `Coordinator` = :executiveCode
  )";
  functioncallfirst($query);
}else if($role == "Master_Code"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = (
      SELECT DISTINCT `Mangers`
      FROM `executivecodes`
      WHERE `Master_Cod` = :executiveCode
  )";
  functioncallfirst($query);
}else{
    $query = "SELECT `category` FROM `categorymanagers`
    WHERE `managercode` = (
        SELECT DISTINCT `Mangers`
        FROM `executivecodes`
        WHERE `Master_Cod` = :executiveCode
    )";
    functioncallfirst($query);
}

function functioncallfirst($query){
    global $pdo;
    global $executiveCode;
    $stmt = $pdo->prepare($query);

// Bind the value of $executiveCode to the :executiveCode placeholder
$stmt->bindParam(':executiveCode', $executiveCode, PDO::PARAM_STR);

// Execute the query
$stmt->execute();

// Fetch the result
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if there are rows
if (!empty($result)) {
    foreach ($result as $row) {
        $category = $row['category'];
       
        if($category == 'domestic' || $category == 'branch'){
          $tableName = 'targetdayrangesdomestic';
          functioncall($tableName);
        }else if($category == 'corporate'){
          $tableName = 'targetdayrangescorporate';
          functioncall($tableName);
        }else if($category == 'db'){
          $tableName = 'targetdayrangesnone';
          functioncall($tableName);
        }
    }
} else {
    echo "No categories found.";
}
}

function functioncall($tableName){

    global $role;
    global $pdo;
    global $executiveCode;
    global $month;
    global $year;

if($role == "Head"){
    $query =   "";
}else if($role == "Manager"){
    $query =   "ec.Mangers = '$executiveCode' AND";
}else if($role == "Coordinator"){
    $query =   "ec.Coordinator = '$executiveCode' AND";
}else if($role == "Master_Code"){
    $query =   "ec.Master_Cod = '$executiveCode' AND";
}

// Prepare and execute the SQL query
$sql = "SELECT * FROM $tableName LIMIT 1, 2";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// Fetch the first record
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Process the result
if ($row) {
    // Access the data from the first record
    $id = $row['id'];
    $day = $row['day'];
    $target = $row['target'];
  
    $sql = "SELECT  SUM(ot.Count) AS Count, SUM(ot.Sum) AS Sum FROM executivecodes AS ec 
    INNER JOIN collectionexectivetargetdata AS ot ON ec.Sub_Code = ot.CollectionExective 
    WHERE $query ot.InYear = '$year' AND ot.InMonth = '$month '";
$queryResult = $pdo->query($sql);

if ($queryResult) {
    $row = $queryResult->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $TotalInvoiceCount = $row['Count'] ?? 0;
        $TotalMoneyCount = $row['Sum'] ?? 0;

        // Rest of the code goes here
    } else {
        // Handle the case when no row is found
    }
} else {
    // Handle the query execution error
}

    
    // Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Prepare and execute the SQL query
$sql = "SELECT SUM(cd.invoicescount) AS sumInvoices, SUM(cd.ccount) AS sumCustomer, SUM(cd.value) AS sumCollectMoney FROM executivecodes AS ec INNER JOIN collectiondata AS cd ON ec.Sub_Code = cd.excode 
WHERE DAY(cd.collectiondate) <= '$day'
AND $query MONTH(cd.collectiondate) = $currentMonth 
AND YEAR(cd.collectiondate) = $currentYear ";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// Fetch the results
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process the results
if ($results) {
    foreach ($results as $row) {
        // Access the data from each row

        $sumCollectMoney = $row['sumCollectMoney'];
        $sumInvoices = $row['sumInvoices'];
        $sumCustomers = $row['sumCustomer'];
        // Remove the '%' symbol from the percentage value and convert it to a decimal
        $percentage = floatval(trim($target, '%')) / 100;
        // Calculate the ActualTarget based on the percentage and the TotalMoneyCount
        $ActualTarget = $TotalMoneyCount * $percentage;
        $ActualPerformance = $sumCollectMoney;
        $Remain = $ActualTarget-$ActualPerformance;
        $Achievement = ($Remain/$ActualTarget)*100;
         if($ActualPerformance == ''){
             $Achievement = '0%';
         }else{
       if ($ActualTarget > 0) {
  $Achievement = ($ActualPerformance / $ActualTarget) * 100;
  $Achievement = max(0, $Achievement); // Ensure the achievement is not negative
  $Achievement = number_format($Achievement, 3) . '%';
} else {
  $Achievement = '0%';
}
}
        // Perform further processing or display the data
       $response = [
  'sumCollectInvoices' => $sumInvoices?? 0,
  'sumCustomers' => $sumCustomers?? 0,
  'ActualTarget' => $ActualTarget?? 0,
  'Value' => $sumCollectMoney?? 0,
  'ActualPerformance' => $ActualPerformance?? 0,
  'Remain' => $Remain?? 0,
  'Achievement' => $Achievement
];

echo json_encode($response);

    }
} else {
   $response = [
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

echo json_encode($response);

}



} else {
    $response = [
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

echo json_encode($response);
}


}


function functioncallfirstadmin($query){
    global $pdo;
    global $executiveCode;
    $stmt = $pdo->prepare($query);  
// Execute the query
$stmt->execute();

// Fetch the result
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if there are rows
if (!empty($result)) {
    $response = []; // Create an array to hold the response data

foreach ($result as $row) {
    $category = $row['category'];
    $managercode = $row['managercode'];

    // Define the table name based on category
    $tableName = ($category == 'domestic') ? 'targetdayrangesdomestic' : 'targetdayrangescorporate';

    // Call the function and store the result
    $targetRanges = functioncalladmin($tableName, $managercode, $category);

    // Add the result to the response array
    $response[$category] = $targetRanges;
}

// Encode the response array as JSON
echo json_encode($response);
} else {
    echo "No categories found.";
}
}

function functioncalladmin($tableName,$executiveCode,$category){
    

    $role='Head';
    global $pdo;
    global $month;
    global $year;

if($role == "Head"){
  
    $query =   "ec.Mangers IN('AJ001', 'BX002')  AND";
    $sql2 = "SELECT 
    SUM(ot.Count) AS Count,
    SUM(ot.Sum) AS Sum
FROM 
    executivecodes AS ec
INNER JOIN 
    collectionexectivetargetdata AS ot ON ec.Sub_Code = ot.CollectionExective
WHERE 
    ec.Mangers IN (
        SELECT managercode
        FROM categorymanagers
        WHERE category = '$category'
    )
    AND ot.InYear = '$year'
    AND ot.InMonth = '$month'
";
}else if($role == "Manager"){
    $query =   "ec.Mangers = '$executiveCode' AND";
    $sql2 = "SELECT  SUM(ot.Count) AS Count, SUM(ot.Sum) AS Sum FROM executivecodes AS ec 
    INNER JOIN collectionexectivetargetdata AS ot ON ec.Sub_Code = ot.CollectionExective 
    WHERE $query ot.InYear = '$year' AND ot.InMonth = '$month '";
}else if($role == "Coordinator"){
    $query =   "ec.Coordinator = '$executiveCode' AND";
    $sql2 = "SELECT  SUM(ot.Count) AS Count, SUM(ot.Sum) AS Sum FROM executivecodes AS ec 
    INNER JOIN collectionexectivetargetdata AS ot ON ec.Sub_Code = ot.CollectionExective 
    WHERE $query ot.InYear = '$year' AND ot.InMonth = '$month '";
}else if($role == "Master_Code"){
    $query =   "ec.Master_Cod = '$executiveCode' AND";
    $sql2 = "SELECT  SUM(ot.Count) AS Count, SUM(ot.Sum) AS Sum FROM executivecodes AS ec 
    INNER JOIN collectionexectivetargetdata AS ot ON ec.Sub_Code = ot.CollectionExective 
    WHERE $query ot.InYear = '$year' AND ot.InMonth = '$month '";
}

// Prepare and execute the SQL query
$sql = "SELECT * FROM $tableName LIMIT 1, 2";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// Fetch the first record
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Process the result
if ($row) {
    // Access the data from the first record
    $id = $row['id'];
    $day = $row['day'];
    $target = $row['target'];
  
    
$queryResult = $pdo->query($sql2);

if ($queryResult) {
    $row = $queryResult->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $TotalInvoiceCount = $row['Count'] ?? 0;
        $TotalMoneyCount = $row['Sum'] ?? 0;

        // Rest of the code goes here
    } else {
        // Handle the case when no row is found
    }
} else {
    // Handle the query execution error
}

    
    // Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// // Prepare and execute the SQL query
// $sql = "SELECT SUM(cd.invoicescount) AS sumInvoices, SUM(cd.ccount) AS sumCustomer, SUM(cd.value) AS sumCollectMoney FROM executivecodes AS ec INNER JOIN collectiondata AS cd ON ec.Sub_Code = cd.excode 
// WHERE DAY(cd.collectiondate) <= '$day'
// AND $query MONTH(cd.collectiondate) = $currentMonth 
// AND YEAR(cd.collectiondate) = $currentYear ";
// $stmt = $pdo->prepare($sql);
// $stmt->execute();

// // Fetch the results
// $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch manager codes from categorymanagers table
$managerCodesQuery = "SELECT managercode FROM categorymanagers WHERE category = '$category'";
$managerCodesStmt = $pdo->prepare($managerCodesQuery);
$managerCodesStmt->execute();
$managerCodesResult = $managerCodesStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($managerCodesResult)) {
    // Handle the case when no manager codes are found
    // You may want to set a default value or return an error
    return ['error' => 'No manager codes found for the given category.'];
}

// Prepare and execute the main SQL query
$managerCodes = implode("','", $managerCodesResult);
$sql = "SELECT SUM(cd.invoicescount) AS sumInvoices, SUM(cd.ccount) AS sumCustomer, SUM(cd.value) AS sumCollectMoney FROM executivecodes AS ec 
        INNER JOIN collectiondata AS cd ON ec.Sub_Code = cd.excode 
        WHERE DAY(cd.collectiondate) <= '$day'
        AND ec.Mangers IN ('$managerCodes') 
        AND MONTH(cd.collectiondate) = $currentMonth 
        AND YEAR(cd.collectiondate) = $currentYear";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// Fetch the results
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process the results...


// Process the results
if ($results) {
    foreach ($results as $row) {
        // Access the data from each row

        $sumCollectMoney = $row['sumCollectMoney'];
        $sumInvoices = $row['sumInvoices'];
        $sumCustomers = $row['sumCustomer'];
        // Remove the '%' symbol from the percentage value and convert it to a decimal
        $percentage = floatval(trim($target, '%')) / 100;
        // Calculate the ActualTarget based on the percentage and the TotalMoneyCount
        $ActualTarget = $TotalMoneyCount * $percentage;
        $ActualPerformance = $sumCollectMoney;
        $Remain = $ActualTarget-$ActualPerformance;
        $Achievement = ($Remain/$ActualTarget)*100;
         if($ActualPerformance == ''){
             $Achievement = '0%';
         }else{
       if ($ActualTarget > 0) {
  $Achievement = ($ActualPerformance / $ActualTarget) * 100;
  $Achievement = max(0, $Achievement); // Ensure the achievement is not negative
  $Achievement = number_format($Achievement, 3) . '%';
} else {
  $Achievement = '0%';
}
}
        // Perform further processing or display the data
       $response = [
  'sumCollectInvoices' => $sumInvoices?? 0,
  'sumCustomers' => $sumCustomers?? 0,
  'ActualTarget' => $ActualTarget?? 0,
  'Value' => $sumCollectMoney?? 0,
  'ActualPerformance' => $ActualPerformance?? 0,
  'Remain' => $Remain?? 0,
  'Achievement' => $Achievement
];

return $response;

    }
} else {
   $response = [
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

echo json_encode($response);

}



} else {
    $response = [
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

echo json_encode($response);
}


}
?>
