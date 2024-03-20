<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");


require_once('../db-config.php');

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
// $role = $data['role'];
// $executiveCode = $data['code'];
$dayrange = $data['dayrange'];
$month = $data['month']; // Convert month name to numeric value
$year = $data['year'];



$date = $year.'-'.$month.'-'.$dayrange;

$sql = "SELECT DISTINCT Mangers, Manger_Name FROM executivecodes";
    
// Prepare the SQL statement
$stmt = $pdo->prepare($sql);

// Execute the statement
$stmt->execute();

// Fetch and loop through the results
// Initialize an empty array to store the results
$resultsArray = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $manager = $row["Mangers"];
    $managername = $row["Manger_Name"];
    $result = getDataForManager($manager, $pdo);

    if ($result) {
        $month = $result['month'];
        $totalSum = $result['total_sum'];
        $year = 2023; // Set the desired year   
    } else {
        $month = 0;
        $totalSum = 0;
        $year = 2023;
    }

    $targetsResult = getTargetsForManager($manager, $pdo, $year, $month);

    if ($targetsResult) {
        $targets = $targetsResult['targets'];
        // Create an associative array with the desired values
        if($targets == 0){
            $totalSum = 0;
        }else{
            $totalSum = $totalSum;
        }
        $resultItem = [
            'ManagerName' => $managername,
            'Manager' => $manager,
            'benchmarkTagetAchieved' => $totalSum,
        ];
    }
    $resultsArray[] = $resultItem;
    $jsonData = json_encode($resultsArray);
        
}
echo $jsonData;

          
          
function getDataForManager($manager, $pdo)
{
  global $date;
  global $dayrange;
    // SQL query to retrieve data for the specified manager
    $sql = "SELECT subquery.month, subquery.total_sum
    FROM (
        SELECT
            DATE_FORMAT(months.month, '%m') AS month,
            SUM(collectiondata.value) AS total_sum
        FROM (
            SELECT
                DATE_ADD('$date', INTERVAL -1 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -2 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -3 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -4 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -5 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -6 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -7 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -8 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -9 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -10 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -11 MONTH) AS month
            UNION ALL
            SELECT
                DATE_ADD('$date', INTERVAL -12 MONTH) AS month
        ) AS months
        LEFT JOIN collectiondata ON 
            DATE_FORMAT(collectiondata.collectiondate, '%Y-%m') = DATE_FORMAT(months.month, '%Y-%m')
        WHERE collectiondata.excode IN (
            SELECT DISTINCT Sub_Code FROM executivecodes WHERE Mangers = :manager
        )
        AND DAY(collectiondata.collectiondate) BETWEEN 1 AND $dayrange -- Change the day range as needed
        GROUP BY month
    ) AS subquery
    ORDER BY subquery.total_sum DESC
    LIMIT 1    
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':manager', $manager, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getTargetsForManager($manager, $pdo, $year, $month)
{
    // SQL query to retrieve targets for the specified manager in a specific year and month
    $sql = "SELECT ec.Manger_Name, ec.Mangers, COALESCE(SUM(NULLIF(ctd.Sum, '')), 0) AS targets
            FROM executivecodes AS ec
            LEFT JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
            WHERE ctd.InYear = 2023 AND ctd.InMonth = :month AND ec.Mangers = :manager
            ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':manager', $manager, PDO::PARAM_STR);
    $stmt->bindParam(':month', $month, PDO::PARAM_INT); // Set the desired month
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


// // Calculate the target months based on the selected month
// $targetMonth1 = ($month - 3) % 12;
// $targetMonth2 = ($month - 2) % 12;
// $targetMonth3 = ($month - 1) % 12;

// // Ensure that the calculated months are positive values (adjusting for negative modulo results)
// $targetMonth1 = $targetMonth1 <= 0 ? $targetMonth1 + 12 : $targetMonth1;
// $targetMonth2 = $targetMonth2 <= 0 ? $targetMonth2 + 12 : $targetMonth2;
// $targetMonth3 = $targetMonth3 <= 0 ? $targetMonth3 + 12 : $targetMonth3;

// $sql = "SELECT
// ec.Manger_Name,
// ec.Mangers,
// SUM(CASE WHEN ctd.InMonth = $targetMonth1 THEN ctd.Sum ELSE 0 END) AS targets_month_3,
// SUM(CASE WHEN ctd.InMonth = $targetMonth2 THEN ctd.Sum ELSE 0 END) AS targets_month_2,
// SUM(CASE WHEN ctd.InMonth = $targetMonth3 THEN ctd.Sum ELSE 0 END) AS targets_month_1,
// GREATEST(
//   SUM(CASE WHEN ctd.InMonth = $targetMonth1 THEN ctd.Sum ELSE 0 END),
//   SUM(CASE WHEN ctd.InMonth = $targetMonth2 THEN ctd.Sum ELSE 0 END),
//   SUM(CASE WHEN ctd.InMonth = $targetMonth3 THEN ctd.Sum ELSE 0 END)
// ) AS highest_target_value_before3month
// FROM
// executivecodes AS ec
// LEFT JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
// AND ctd.InYear = '$year'
// AND ctd.InMonth IN ($targetMonth1, $targetMonth2, $targetMonth3)
// GROUP BY
// ec.Mangers
// ORDER BY
// ec.Mangers";

// $stmt = $pdo->prepare($sql);
// $stmt->execute();
// // Fetch the results
// $options = [];

// while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//     $options[] = array(
//         'ManagerName' => $row['Manger_Name'],
//         'Manager' => $row['Mangers'],
//         'highest_target_value_before3month' => $row['highest_target_value_before3month']
//     );
//     $options[] = $row;
// }

// // Return the response
// echo json_encode($options);

?>
