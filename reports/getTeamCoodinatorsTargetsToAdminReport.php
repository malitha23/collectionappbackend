<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php'); // Make sure this file exists and contains the database connection code

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
$dayrange = $data['dayrange'];
$month = $data['month'];
$managercode = $data['managercode'];
$year = $data['year'];
// $dayrange = 30;
// $month = 9; // Convert month name to numeric value
// $year = 2023;
// $managercode = 'AJ001';

   $getcoordinatorstargets = getcoordinatorstargets($pdo);
   $getcoordinatorstargetsachive = getcoordinatorstargetsachived($pdo);
   $getcoordinatorstargetsforbentchmark = getcoordinatorstargetsforbentchmark($pdo);
// Merge the two arrays into one
$combinedData = [];

// Iterate through the first array and build the combined data
foreach ($getcoordinatorstargets as $row) {
    $coordinator = $row["Coordinator"];
    
    // Initialize the entry if it doesn't exist
    if (!isset($combinedData[$coordinator])) {
        $combinedData[$coordinator] = $row;
    } else {
        // Merge the data from the other arrays
        $combinedData[$coordinator] = array_merge($combinedData[$coordinator], $row);
    }
}

// Iterate through the second array and update the combined data
foreach ($getcoordinatorstargetsachive as $row) {
    $coordinator = $row["Coordinator"];
    
    // Update the existing entry with data from this array
    if (isset($combinedData[$coordinator])) {
        $combinedData[$coordinator] = array_merge($combinedData[$coordinator], $row);
    }
}

// Iterate through the third array and update the combined data
foreach ($getcoordinatorstargetsforbentchmark as $row) {
    $coordinator = $row["Coordinator"];
    
    // Update the existing entry with data from this array
    if (isset($combinedData[$coordinator])) {
        $combinedData[$coordinator] = array_merge($combinedData[$coordinator], $row);
    }
}

// Convert the combined data into a list
$combinedData = array_values($combinedData);

// Return the combined data as JSON
header('Content-Type: application/json');
echo json_encode($combinedData);



function getcoordinatorstargets($pdo){

 global $dayrange;
 global $month; 
 global $year;
 global $managercode;   

// Prepare the SQL statement using a parameterized query
$sql = "SELECT ec.Coordinator_Name, ec.Coordinator, SUM(ctd.Sum) AS targets 
FROM executivecodes AS ec 
INNER JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
WHERE ctd.InYear = :year AND ctd.InMonth = :month AND ec.Mangers = :manager
GROUP BY ec.Coordinator_Name";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':year', $year, PDO::PARAM_INT);
$stmt->bindParam(':month', $month, PDO::PARAM_INT);
$stmt->bindParam(':manager', $managercode );
$stmt->execute();

// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'Coordinator_Name' => $row['Coordinator_Name'],
        'Coordinator' => $row['Coordinator'],
        'targets' => $row['targets']
    );
}
return $options;

}

function getcoordinatorstargetsachived($pdo)
{
 global $dayrange;
 global $month; 
 global $year;
 global $managercode; 

    $sql = "SELECT ec.Coordinator_Name, ec.Coordinator, COALESCE(SUM(ctd.value), 0) AS firstTagetAchieved 
    FROM executivecodes AS ec 
    LEFT JOIN collectiondata AS ctd ON ctd.excode = ec.Sub_Code 
    WHERE 
        (MONTH(ctd.collectiondate) = :month
        AND YEAR(ctd.collectiondate) = :year
        AND DAY(ctd.collectiondate) BETWEEN 1 AND :dayrange
        AND ec.Mangers = :manager) 
        OR 
        (ctd.collectiondate IS NULL AND ec.Mangers = :manager)
    GROUP BY ec.Coordinator_Name ";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':year', $year, PDO::PARAM_INT);
$stmt->bindParam(':month', $month, PDO::PARAM_INT);
$stmt->bindParam(':dayrange', $dayrange, PDO::PARAM_INT);
$stmt->bindParam(':manager', $managercode );
$stmt->execute();


// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'Coordinator_Name' => $row['Coordinator_Name'],
        'Coordinator' => $row['Coordinator'],
        'firstTagetAchieved' => $row['firstTagetAchieved']
    );
}
 
return $options; 
}

function getcoordinatorstargetsforbentchmark($pdo){

global $dayrange;
global $month; 
global $year;
global $managercode;
$date = $year.'-'.$month.'-'.$dayrange;

$sql = "SELECT DISTINCT `Coordinator`, `Coordinator_Name` FROM executivecodes WHERE `Mangers` = '$managercode' ";
    
// Prepare the SQL statement
$stmt = $pdo->prepare($sql);

// Execute the statement
$stmt->execute();

// Fetch and loop through the results
// Initialize an empty array to store the results
$resultsArray = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $Coordinator = $row["Coordinator"];
    $CoordinatorName = $row["Coordinator_Name"];
    $result = getDataForCoordinator($Coordinator, $pdo, $date);
   
    if ($result) {
        
        $month = $result['month'];
        $totalSum = $result['total_sum'];
        $year = 2023; // Set the desired year   
    } else {
        $month = 0;
        $totalSum = 0;
        $year = 2023;
    }
 
     $targetsResult = getTargetsForCoordinator($Coordinator, $pdo, $year, $month);
    if ($targetsResult) {
        $targets = $targetsResult['targets'];
        // Create an associative array with the desired values
        $resultItem = [
            'Coordinator_Name' => $CoordinatorName,
            'Coordinator' => $Coordinator,
            'highest_target_value_before12month' => $targets,
            'benchmarkTagetAchieved' => $totalSum,
        ];
    }
    $resultsArray[] = $resultItem;
    
}
return $resultsArray;
}

function getTargetsForCoordinator($coordinator, $pdo, $year, $month)
{
    // SQL query to retrieve targets for the specified manager in a specific year and month
    $sql = "SELECT ec.Manger_Name, ec.Mangers, COALESCE(SUM(NULLIF(ctd.Sum, '')), 0) AS targets
            FROM executivecodes AS ec
            LEFT JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
            WHERE ctd.InYear = :year AND ctd.InMonth = :month AND ec.Coordinator = :coordinator
            ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':coordinator', $coordinator, PDO::PARAM_STR);
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->bindParam(':month', $month, PDO::PARAM_INT); // Set the desired month
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDataForCoordinator($coordinator, $pdo, $date)
{
  
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
            SELECT DISTINCT Sub_Code FROM executivecodes WHERE Coordinator = :coordinator
        )
        AND DAY(collectiondata.collectiondate) BETWEEN 1 AND $dayrange -- Change the day range as needed
        GROUP BY month
    ) AS subquery
    ORDER BY subquery.total_sum DESC
    LIMIT 1    
    ";

    $stmt = $pdo->prepare($sql);
     $stmt->bindParam(':coordinator', $coordinator, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
