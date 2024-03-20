<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');


// Get the request payload
$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

$month = $requestPayload['month'];
$executiveCode = $requestPayload['Executivecode'];

$year = date('Y');
$startDate = "{$year}-{$month}-01";
$endDate = "{$year}-{$month}-31";
$nowstartDate = "{$year}-" . ($month) . "-01";
$nowendDate = "{$year}-" . ($month) . "-31";

$sql = "SELECT SUM(cetd.Sum) AS Sum , SUM(cetd.Count) AS Count FROM `executivecodes` AS ec JOIN `collectionexectivetargetdata` AS cetd ON ec.`Sub_Code` = cetd.`CollectionExective` WHERE ec.`Master_Cod` = '$executiveCode' AND cetd.`InMonth` = '$month'";

$queryResult = $pdo->query($sql);

if ($queryResult) {
    $row = $queryResult->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $TotalInvoiceCount = $row['Count'];
        $TotalMoneyCount = $row['Sum'];
        if($TotalMoneyCount == null){
            $TotalMoneyCount = 0; 
        }
        $TotalCollectInvoice = 0;
        $TotalCustomerCount = 0;
        $sql = "SELECT SUM(invoicescount) AS sumInvoices, SUM(ccount) AS sumCustomer, SUM(value) AS sumCollectMoney  
                FROM collectiondata 
                WHERE excode = '$executiveCode' AND collectiondate BETWEEN :startDate AND :endDate";
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
