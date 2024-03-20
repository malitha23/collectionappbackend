<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../db-config.php');

// Get the request payload
$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

// // Extract the login data
$month = $requestPayload['month'];
$executiveCode = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];
// $month = 8;
// $executiveCode = 'BX002';
// $role = 'Manager';

if($role == "Head"){
    $query =   "";
}else if($role == "Manager"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = :executiveCode ";
}else if($role == "Coordinator"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = (
      SELECT DISTINCT `Mangers`
      FROM `executivecodes`
      WHERE `Coordinator` = :executiveCode
  )";
}else if($role == "Master_Code"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = (
      SELECT DISTINCT `Mangers`
      FROM `executivecodes`
      WHERE `Master_Cod` = :executiveCode
  )";
}else{
    $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = (
      SELECT DISTINCT `Mangers`
      FROM `executivecodes`
      WHERE `Master_Cod` = :executiveCode
  )";
}

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

function functioncall($targetdayranges){

global $pdo;    
global $executiveCode; 
global $month;
// Prepare and execute the SQL query
$sql = "SELECT * FROM $targetdayranges LIMIT 1";
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
    
    $sql = "SELECT SUM(cetd.Sum) AS Sum , SUM(cetd.Count) AS Count FROM `executivecodes` AS ec JOIN `collectionexectivetargetdata` AS cetd ON ec.`Sub_Code` = cetd.`CollectionExective` WHERE ec.`Master_Cod` = '$executiveCode' AND cetd.`InMonth` = '$month'";
    $queryResult = $pdo->query($sql);

if ($queryResult) {
    $row = $queryResult->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $TotalInvoiceCount = $row['Count'];
        $TotalMoneyCount = $row['Sum'];
       
        // Rest of the code goes here
    } else {
        echo "dd";
        // Handle the case when no row is found
    }
} else {
    // Handle the query execution error
}

 if($TotalMoneyCount != null){   
    // Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Prepare and execute the SQL query
$sql = "SELECT SUM(invoicescount) AS sumInvoices, SUM(ccount) AS sumCustomer, SUM(value) AS sumCollectMoney FROM collectiondata WHERE DAY(collectiondate) <= '$day' AND excode = '$executiveCode' AND MONTH(collectiondate) = :currentMonth AND YEAR(collectiondate) = :currentYear";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':currentMonth', $currentMonth);
$stmt->bindParam(':currentYear', $currentYear);
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
  'sumCollectInvoices' => $sumInvoices,
  'sumCustomers' => $sumCustomers,
  'ActualTarget' => $ActualTarget,
  'Value' => $sumCollectMoney,
  'ActualPerformance' => $ActualPerformance,
  'Remain' => $Remain,
  'Achievement' => $Achievement
];

echo json_encode($response);

    }
} else {
   $response = [
  'sumCollectInvoices' => 0,
  'sumCustomers' => 0,
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

echo json_encode($response);

}

}else{
    $response = [
        'sumCollectInvoices' => 0,
        'sumCustomers' => 0,
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
        'sumCollectInvoices' => 0,
        'sumCustomers' => 0,
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
