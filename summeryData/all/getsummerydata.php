<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../../db-config.php');


// Get the request payload
$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

$month = $requestPayload['month'];
$executiveCode = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];

if($role == "Head"){
    $query =   "";
}else if($role == "Manager"){
    $query =   "ec.Mangers = '$executiveCode' AND";
}else if($role == "Coordinator"){
    $query =   "ec.Coordinator = '$executiveCode' AND";
}else if($role == "Master_Code"){
    $query =   "ec.Master_Cod = '$executiveCode' AND";
}

$year = date('Y');
$startDate = "{$year}-{$month}-01";
$endDate = "{$year}-{$month}-31";
$nowstartDate = "{$year}-" . ($month) . "-01";
$nowendDate = "{$year}-" . ($month) . "-31";

$sql = "SELECT  SUM(ot.Count) AS Count, SUM(ot.Sum) AS Sum FROM executivecodes AS ec 
INNER JOIN collectionexectivetargetdata AS ot ON ec.Sub_Code = ot.CollectionExective 
WHERE $query ot.InYear = '$year' AND ot.InMonth = '$month '";

$queryResult = $pdo->query($sql);

if ($queryResult) {
    $row = $queryResult->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $TotalInvoiceCount = $row['Count'];
        $TotalMoneyCount = $row['Sum'];
        // if($TotalMoneyCount == null){
        //     // Get the current month name
        //     date_default_timezone_set('Asia/Colombo');
        //     $currentMonthName = date('F');
        //     $response['success'] = false;
        //     $response['message'] = $currentMonthName.' Target not updated';         
        //     echo json_encode($response);
        //     return;
        // }
        $TotalCollectInvoice = 0;
        $TotalCustomerCount = 0;
        $sql = "SELECT
        SUM(ot.invoicescount) AS sumInvoices,
        SUM(ot.ccount) AS sumCustomer,
        SUM(ot.value) AS sumCollectMoney
 FROM executivecodes AS ec
 INNER JOIN collectiondata AS ot ON ec.Sub_Code = ot.excode
 WHERE $query  ot.collectiondate BETWEEN :startDate AND :endDate";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':startDate', $nowstartDate);
        $stmt->bindParam(':endDate', $nowendDate);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['sumInvoices'])) {
                
                $TotalCollectMoney = $result['sumCollectMoney'];
                $TotalCollectInvoice = $result['sumInvoices'];
                $TotalCustomerCount = $result['sumCustomer'];
                $remain = $TotalMoneyCount - $TotalCollectMoney;
                $achievement = number_format(($TotalCollectMoney / $TotalMoneyCount) * 100, 3) . '%';

                $response = [
                    'TotalInvoiceCount' => $TotalCollectInvoice,
                    'TotalMoneyCount' => $TotalMoneyCount,
                    'TotalCollectMoney' => $TotalCollectMoney,
                    'TotalCustomerCount' => $TotalCustomerCount,
                    'remain' => $remain,
                    'achievement' => $achievement
                ];

                echo json_encode($response);
            } else {
                 echo json_encode([
                    'TotalInvoiceCount' => $TotalCollectInvoice,
                    'TotalMoneyCount' => $TotalMoneyCount,
                    'TotalCollectMoney' => 0,
                    'TotalCustomerCount' => $TotalCustomerCount,
                    'remain' => $TotalMoneyCount,
                    'achievement' => '0%'
                ]);
            }
        } else {
            $error = $stmt->errorInfo();
            // Handle the query execution error
        }
    }
} else {
    $Sum = 0;
    $invoiceCount = 0;
    $error = $pdo->errorInfo();
    // Handle the query error
}

?>
