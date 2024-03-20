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

// $start_date = '2023-09-01';
// $end_date = '2023-09-30';
// $executiveCode = 'EX0057';
// $role = 'Master_Code';
$start_date = $requestPayload['start_date'];
$end_date = $requestPayload['end_date'];
$executiveCode = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];
function HeadfetchRemarksData($executiveCode, $start_date, $end_date, $role, $pdo, $query1) {
    $sql = "SELECT `Mangers`, `Manger_Name`, `Coordinator`, `Coordinator_Name`, `Master_Cod` FROM `executivecodes` GROUP BY `Master_Cod`";

    // Prepare and execute the query
    $stmt = $pdo->query($sql);

    // Fetch the results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response = array();
    foreach ($results as $row) {
        $masterCod = $row['Master_Cod'];
        $Coordinator_Name = $row['Coordinator_Name'];
        $CoordinatorCod = $row['Coordinator'];
        $Mangers_Name = $row['Manger_Name'];
        $MangersCod = $row['Mangers'];
        // Query 1
        $stmt1 = $pdo->prepare($query1);
        $stmt1->bindParam(':excode', $masterCod);
        $stmt1->bindParam(':start_date', $start_date);
        $stmt1->bindParam(':end_date', $end_date);
        $stmt1->execute();
        $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // Check if $result1 is not empty before accessing its elements
        if (!empty($result1)) {
            // Loop through $result1 if there are multiple rows, or use $result1[0] if you expect only one row
            foreach ($result1 as $row1) {
                $response[] = array(
                    'notConform' => isset($row1['notConform']) ? $row1['notConform'] : null,
                    'pending' => isset($row1['pending']) ? $row1['pending'] : null,
                    'closed' => isset($row1['closed']) ? $row1['closed'] : null
                );
            }
        }
    }
    $pdo = null;
    return $response;
}

if($role == "Head"){
    $query1 = "SELECT
    ec.Master_exe_Name AS `Name`,
    ec.Master_Cod AS `Code`,
    rd.Date,
    rd.notConform,
    rd.pending,
    rd.closed
FROM
    (
        SELECT
            DATE(remarksdate) AS Date,
            SUM(CASE WHEN status = -1 THEN 1 ELSE 0 END) AS notConform,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS closed
        FROM
            remarksdata
        WHERE
            excode = :excode
            AND remarksdate BETWEEN :start_date AND :end_date
        GROUP BY
            DATE(remarksdate)
    ) AS rd
JOIN
    (
        SELECT DISTINCT
            Master_exe_Name, Master_Cod
        FROM
            executivecodes
        WHERE
        Sub_Code = :excode
    ) AS ec
ON
    1 = 1
ORDER BY
    rd.Date
";
    $results = HeadfetchRemarksData($executiveCode, $start_date, $end_date, $role, $pdo, $query1);
    $sums = ['notConform' => 0, 'pending' => 0, 'closed' => 0];

     foreach ($results as $item) {
      foreach ($sums as $key => &$sum) {
         $sum += isset($item[$key]) ? (int)$item[$key] : 0;
      }
     }
    echo json_encode($sums);
}else if($role == "Manager"){
    $query1 = "SELECT
    ec.Master_exe_Name AS `Name`,
    ec.Master_Cod AS `Code`,
    rd.Date,
    rd.notConform,
    rd.pending,
    rd.closed
FROM
    (
        SELECT
            DATE(remarksdate) AS Date,
            SUM(CASE WHEN status = -1 THEN 1 ELSE 0 END) AS notConform,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS closed
        FROM
            remarksdata
        WHERE
            excode = :excode
            AND remarksdate BETWEEN :start_date AND :end_date
        GROUP BY
            DATE(remarksdate)
    ) AS rd
JOIN
    (
        SELECT DISTINCT
            Master_exe_Name, Master_Cod
        FROM
            executivecodes
        WHERE
        Sub_Code = :excode
    ) AS ec
ON
    1 = 1
ORDER BY
    rd.Date
";
    $results = ManagerfetchRemarksData($executiveCode, $start_date, $end_date, $role, $pdo, $query1);
    $sums = ['notConform' => 0, 'pending' => 0, 'closed' => 0];

    foreach ($results as $item) {
     foreach ($sums as $key => &$sum) {
        $sum += isset($item[$key]) ? (int)$item[$key] : 0;
     }
    }
   echo json_encode($sums);
}else if($role == "Coordinator"){
    $query1 = "SELECT
    ec.Master_exe_Name AS `Name`,
    ec.Master_Cod AS `Code`,
    rd.Date,
    rd.notConform,
    rd.pending,
    rd.closed
FROM
    (
        SELECT
            DATE(remarksdate) AS Date,
            SUM(CASE WHEN status = -1 THEN 1 ELSE 0 END) AS notConform,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS closed
        FROM
            remarksdata
        WHERE
            excode = :excode
            AND remarksdate BETWEEN :start_date AND :end_date
        GROUP BY
            DATE(remarksdate)
    ) AS rd
JOIN
    (
        SELECT DISTINCT
            Master_exe_Name, Master_Cod
        FROM
            executivecodes
        WHERE
        Sub_Code = :excode
    ) AS ec
ON
    1 = 1
ORDER BY
    rd.Date
";
    $results = CoordinatorfetchRemarksData($executiveCode, $start_date, $end_date, $role, $pdo, $query1);
    $sums = ['notConform' => 0, 'pending' => 0, 'closed' => 0];

     foreach ($results as $item) {
      foreach ($sums as $key => &$sum) {
         $sum += isset($item[$key]) ? (int)$item[$key] : 0;
      }
     }
    echo json_encode($sums);
}else if($role == "Master_Code"){
    $query1 = "SELECT
    rd.notConform,
    rd.pending,
    rd.closed
FROM
    (
        SELECT
            DATE(remarksdate) AS Date,
            SUM(CASE WHEN status = -1 THEN 1 ELSE 0 END) AS notConform,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS closed
        FROM
            remarksdata
        WHERE
            excode = :excode
            AND remarksdate BETWEEN :start_date AND :end_date
        GROUP BY
            DATE(remarksdate)
    ) AS rd
JOIN
    (
        SELECT DISTINCT
            Master_exe_Name, Master_Cod
        FROM
            executivecodes
        WHERE
        Sub_Code = :excode
    ) AS ec
ON
    1 = 1
ORDER BY
    rd.Date
";
    $results = Master_CodefetchRemarksData($executiveCode, $start_date, $end_date, $role, $pdo, $query1);
    $sums = ['notConform' => 0, 'pending' => 0, 'closed' => 0];

    foreach ($results as $item) {
     foreach ($sums as $key => &$sum) {
        $sum += isset($item[$key]) ? (int)$item[$key] : 0;
     }
    }
   echo json_encode($sums);
}else{
   
}


function Master_CodefetchRemarksData($executiveCode, $start_date, $end_date, $role, $pdo, $query1) {
         
            // Query 1
            $stmt1 = $pdo->prepare($query1);
            $stmt1->bindParam(':excode', $executiveCode);
            $stmt1->bindParam(':start_date', $start_date);
            $stmt1->bindParam(':end_date', $end_date);
            $stmt1->execute();
            $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        return $result1;
}

function CoordinatorfetchRemarksData($executiveCode, $start_date, $end_date, $role, $pdo, $query1) {
    $sql = "SELECT DISTINCT Master_Cod FROM executivecodes WHERE Coordinator = '$executiveCode'";

    // Prepare and execute the query
    $stmt = $pdo->query($sql);

    // Fetch the results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response = array();
    foreach ($results as $row) {
        $masterCod = $row['Master_Cod'];

        // Query 1
        $stmt1 = $pdo->prepare($query1);
        $stmt1->bindParam(':excode', $masterCod);
        $stmt1->bindParam(':start_date', $start_date);
        $stmt1->bindParam(':end_date', $end_date);
        $stmt1->execute();
        $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // Check if $result1 is not empty before accessing its elements
        if (!empty($result1)) {
            // Loop through $result1 if there are multiple rows, or use $result1[0] if you expect only one row
            foreach ($result1 as $row1) {
                $response[] = array(
                    'notConform' => isset($row1['notConform']) ? $row1['notConform'] : null,
                    'pending' => isset($row1['pending']) ? $row1['pending'] : null,
                    'closed' => isset($row1['closed']) ? $row1['closed'] : null
                );
            }
        }
    }
    $pdo = null;
    return $response;
}

function ManagerfetchRemarksData($executiveCode, $start_date, $end_date, $role, $pdo, $query1) {
    $sql = "SELECT `Coordinator`,`Coordinator_Name`, `Master_Cod` FROM executivecodes WHERE `Mangers` = '$executiveCode' GROUP BY `Master_Cod`";

    // Prepare and execute the query
    $stmt = $pdo->query($sql);

    // Fetch the results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response = array();
    foreach ($results as $row) {
        $masterCod = $row['Master_Cod'];
        $Coordinator_Name = $row['Coordinator_Name'];
        $CoordinatorCod = $row['Coordinator'];
        // Query 1
        $stmt1 = $pdo->prepare($query1);
        $stmt1->bindParam(':excode', $masterCod);
        $stmt1->bindParam(':start_date', $start_date);
        $stmt1->bindParam(':end_date', $end_date);
        $stmt1->execute();
        $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // Check if $result1 is not empty before accessing its elements
        if (!empty($result1)) {
            // Loop through $result1 if there are multiple rows, or use $result1[0] if you expect only one row
            foreach ($result1 as $row1) {
                $response[] = array(
                    'notConform' => isset($row1['notConform']) ? $row1['notConform'] : null,
                    'pending' => isset($row1['pending']) ? $row1['pending'] : null,
                    'closed' => isset($row1['closed']) ? $row1['closed'] : null
                );
            }
        }
    }
    $pdo = null;
    return $response;
}
?>