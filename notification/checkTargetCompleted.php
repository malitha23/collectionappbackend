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
$Code = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];
$formattedDateTime = $requestPayload['formattedDateTime'];
$range = $requestPayload['range'];
$year = date('Y');


if($role == "Head"){
    $query2 = "SELECT DISTINCT `Mangers` FROM `executivecodes`";
    $stmt = $pdo->prepare($query2);

}else if($role == "Manager"){
    $query2 = "SELECT DISTINCT `Coordinator` FROM `executivecodes` WHERE `Mangers` = :Cod";
    $stmt = $pdo->prepare($query2);
    $stmt->bindParam(':Cod', $Code, PDO::PARAM_STR);
}else if($role == "Coordinator"){
    $query2 = "SELECT DISTINCT `Master_Cod` FROM `executivecodes` WHERE `Coordinator` = :Cod";
    $stmt = $pdo->prepare($query2);
    $stmt->bindParam(':Cod', $Code, PDO::PARAM_STR);
}else if($role == "Master_Code"){
    $query2 = "SELECT DISTINCT `Sub_Code` FROM `executivecodes` WHERE `Master_Cod` = :Cod";
    $stmt = $pdo->prepare($query2);
    $stmt->bindParam(':Cod', $Code, PDO::PARAM_STR);
}

try {
    $stmt->execute();
    $subCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

// Output the retrieved Sub_Code values
foreach ($subCodes as $subCode) {
    

$executiveCode = $subCode;

if($role == "Head"){
    $query =   "ec.Mangers = '$executiveCode' AND";
    checkfirsttargetcompleted($pdo ,$query, $month, $year, $executiveCode, $range);
}else if($role == "Manager"){
    $query =   "ec.Coordinator = '$executiveCode' AND";
    checkfirsttargetcompleted($pdo ,$query, $month, $year, $executiveCode, $range);
}else if($role == "Coordinator"){
    $query =   "ec.Master_Cod = '$executiveCode' AND";
    checkfirsttargetcompleted($pdo ,$query, $month, $year, $executiveCode, $range);
}else if($role == "Master_Code"){
    checkfirsttargetcompletedsubcode($pdo, $month, $executiveCode, $range);
}
}


function  checkfirsttargetcompleted($pdo ,$query, $month, $year, $executiveCode, $range){

if($range == 1){
    $sql = "SELECT * FROM targetdayranges LIMIT 1";
}else if($range == 2){
    $sql = "SELECT * FROM targetdayranges LIMIT 1, 1";
}else if($range == 3){
    $sql = "SELECT * FROM targetdayranges LIMIT 2, 1";
}else if($range == 4){
    $sql = "SELECT * FROM targetdayranges LIMIT 3, 1";
}     
    // Prepare and execute the SQL query

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
        if($ActualTarget > 0){
            $Achievement = ($Remain/$ActualTarget)*100;
        }else{
            $Achievement = 0;
        }
   
        
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
Achievementcheckcompleted($pdo, $Achievement, $executiveCode, $day);
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

// echo json_encode($response);

    }
} else {
   $response = [
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

// echo json_encode($response);

}



} else {
    $response = [
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

// echo json_encode($response);
}

}

function checkfirsttargetcompletedsubcode($pdo, $month, $executiveCode, $range){

    if($range == 1){
        $sql = "SELECT * FROM targetdayranges LIMIT 1";
    }else if($range == 2){
        $sql = "SELECT * FROM targetdayranges LIMIT 1, 1";
    }else if($range == 3){
        $sql = "SELECT * FROM targetdayranges LIMIT 2, 1";
    }else if($range == 4){
        $sql = "SELECT * FROM targetdayranges LIMIT 3, 1";
    } 

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
    
    $sql = "SELECT * FROM `CollectionExectiveTargetData` WHERE `CollectionExective` = '$executiveCode' AND `InMonth` = '$month' ";
$queryResult = $pdo->query($sql);

if ($queryResult) {
    $row = $queryResult->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $TotalInvoiceCount = $row['Count'];
        $TotalMoneyCount = $row['Sum'];

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
Achievementcheckcompleted($pdo, $Achievement, $executiveCode, $day);

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

// echo json_encode($response);

    }
} else {
   $response = [
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

// echo json_encode($response);

}



} else {
    $response = [
  'ActualTarget' => 0,
  'Value' => 0,
  'ActualPerformance' => 0,
  'Remain' => 0,
  'Achievement' => '0%'
];

// echo json_encode($response);
}



}


function Achievementcheckcompleted($pdo, $Achievement1, $executiveCode, $day){
    global $Code;
    global $role;
    global $formattedDateTime;
   
    if($role == "Head"){
     $query = "SELECT DISTINCT `Manger_Name` FROM `executivecodes` WHERE `Mangers` = '$executiveCode'";
    }else if($role == "Manager"){
        $query = "SELECT DISTINCT `Coordinator_Name` FROM `executivecodes` WHERE `Coordinator` = '$executiveCode'";
    }else if($role == "Coordinator"){
        $query = "SELECT DISTINCT `Master_exe_Name` FROM `executivecodes` WHERE `Sub_Code` = '$executiveCode'";
        
    }else if($role == "Master_Code"){
        $query = "SELECT DISTINCT `Master_exe_Name` FROM `executivecodes` WHERE `Sub_Code` = '$executiveCode'";
    }
    
    $percentageWithoutSymbol = str_replace('%', '', $Achievement1);
    if($percentageWithoutSymbol >= 100){
     
        try {
            $stmt = $pdo->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
        
        foreach ($results as $name) {
            // Check if a notification already exists for the specified executive code and summary
    $existingNotificationSql = "SELECT COUNT(*) FROM `notifications` WHERE `excode` = :executiveCode AND `detail` = :detail";
    $existingNotificationStmt = $pdo->prepare($existingNotificationSql);
    $existingNotificationStmt->bindValue(':executiveCode', $Code);
    $existingNotificationStmt->bindValue(':detail', $name.' 1 to '. $day. ' Days Collections Target Completed!');
    $existingNotificationStmt->execute();
    $existingCount = $existingNotificationStmt->fetchColumn();

    if ($existingCount < 1) {
        
        // Insert the notification
        $insertNotificationSql = "INSERT INTO `notifications` (`severity`, `summary`, `detail`, `role`, `excode`, `status`, `created_at`, `displayed`) VALUES (:severity, :summary, :detail, :role, :executiveCode, '1', '$formattedDateTime', '0')";
        $notificationStmt = $pdo->prepare($insertNotificationSql);
        $notificationStmt->bindValue(':severity', 'success');
        $notificationStmt->bindValue(':summary', 'Alert');
        $notificationStmt->bindValue(':detail', $name.' 1 to '. $day. ' Days Collections Target Completed!');
        $notificationStmt->bindValue(':role', $role);
        $notificationStmt->bindValue(':executiveCode', $Code);
        $notificationStmt->execute();
    } else {
        
        // // Update the existing notification
        // $updateNotificationSql = "UPDATE `notifications` SET `severity` = :severity, `summary` = :summary, `detail` = :detail, `created_at` = :formattedDateTime, `status`='1', `displayed` = '0' WHERE `role` = :role AND `excode` = :executiveCode AND `summary` = 'Alert' AND `detail` = :detail ";
        // $updateNotificationStmt = $pdo->prepare($updateNotificationSql);
        // $updateNotificationStmt->bindValue(':severity', 'success');
        // $updateNotificationStmt->bindValue(':summary', 'Alert');
        // $updateNotificationStmt->bindValue(':detail', $name.' 1 to '. $day. ' Days Collections Target Completed!');
        // $updateNotificationStmt->bindValue(':executiveCode', $Code);
        // $updateNotificationStmt->bindValue(':formattedDateTime', $formattedDateTime);
        // $updateNotificationStmt->bindValue(':role', $role);
        // $updateNotificationStmt->execute();
        
    }
        }

    }

    
    
}




?>
