<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php'); // Make sure this file exists and contains the database connection code

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
$dayrange = $data['dayrange'];
$month = $data['month'];
$coordinatorcode = $data['coordinatorcode'];
$year = $data['year'];
// $dayrange = 30;
// $month = 9; // Convert month name to numeric value
// $year = 2023;
// $coordinatorcode = 'EX1491';

   $getmasterstargets = getcoordinatorstargets($pdo);
   $getmasterstargetsachive = getcoordinatorstargetsachived($pdo);
   $getmasterstargetsforbentchmark = getcoordinatorstargetsforbentchmark($pdo);
// Merge the two arrays into one
$combinedData = [];

// // Iterate through the first array and build the combined data
foreach ($getmasterstargets as $row) {
    $Master_Cod = $row["Master_Cod"];
    
    // Initialize the entry if it doesn't exist
    if (!isset($combinedData[$Master_Cod])) {
        $combinedData[$Master_Cod] = $row;
    } else {
        // Merge the data from the other arrays
        $combinedData[$Master_Cod] = array_merge($combinedData[$Master_Cod], $row);
    }
}

// // Iterate through the second array and update the combined data
foreach ($getmasterstargetsachive as $row) {
    $Master_Cod = $row["Master_Cod"];
    
    // Update the existing entry with data from this array
    if (isset($combinedData[$Master_Cod])) {
        $combinedData[$Master_Cod] = array_merge($combinedData[$Master_Cod], $row);
    }
}

// // Iterate through the third array and update the combined data
foreach ($getmasterstargetsforbentchmark as $row) {
    $Master_Cod = $row["Master_Cod"];
    
    // Update the existing entry with data from this array
    if (isset($combinedData[$Master_Cod])) {
        $combinedData[$Master_Cod] = array_merge($combinedData[$Master_Cod], $row);
    }
}

// // Convert the combined data into a list
$combinedData = array_values($combinedData);

// Return the combined data as JSON
header('Content-Type: application/json');
echo json_encode($combinedData);




function getcoordinatorstargets($pdo){

 global $dayrange;
 global $month; 
 global $year;
 global $coordinatorcode;   

// Prepare the SQL statement using a parameterized query
$sql = "SELECT ec.Master_exe_Name, ec.Master_Cod, SUM(ctd.Sum) AS targets 
FROM executivecodes AS ec 
INNER JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
WHERE ctd.InYear = :year AND ctd.InMonth = :month AND ec.Coordinator = :coordinator
GROUP BY ec.Master_Cod";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':year', $year, PDO::PARAM_INT);
$stmt->bindParam(':month', $month, PDO::PARAM_INT);
$stmt->bindParam(':coordinator', $coordinatorcode );
$stmt->execute();

// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'Master_exe_Name' => $row['Master_exe_Name'],
        'Master_Cod' => $row['Master_Cod'],
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
 global $coordinatorcode; 

    $sql = "SELECT ec.Master_exe_Name, ec.Master_Cod, COALESCE(SUM(ctd.value), 0) AS firstTagetAchieved 
    FROM executivecodes AS ec 
    LEFT JOIN collectiondata AS ctd ON ctd.excode = ec.Sub_Code 
    WHERE 
        (MONTH(ctd.collectiondate) = :month
        AND YEAR(ctd.collectiondate) = :year
        AND DAY(ctd.collectiondate) BETWEEN 1 AND :dayrange
        AND ec.Coordinator = :coordinator) 
        OR 
        (ctd.collectiondate IS NULL AND ec.Coordinator = :coordinator)
    GROUP BY ec.Master_Cod ";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':year', $year, PDO::PARAM_INT);
$stmt->bindParam(':month', $month, PDO::PARAM_INT);
$stmt->bindParam(':dayrange', $dayrange, PDO::PARAM_INT);
$stmt->bindParam(':coordinator', $coordinatorcode );
$stmt->execute();


// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'Master_exe_Name' => $row['Master_exe_Name'],
        'Master_Cod' => $row['Master_Cod'],
        'firstTagetAchieved' => $row['firstTagetAchieved']
    );
}
 
return $options; 
}

function getcoordinatorstargetsforbentchmark($pdo){

global $dayrange;
global $month; 
global $year;
global $coordinatorcode;
$date = $year.'-'.$month.'-'.$dayrange;

$sql = "SELECT `Master_Cod`, `Master_exe_Name` FROM executivecodes WHERE `Coordinator` = :coordinatorcode GROUP BY `Master_Cod` ";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':coordinatorcode', $coordinatorcode, PDO::PARAM_STR);
$stmt->execute();


// Fetch and loop through the results
// Initialize an empty array to store the results
$resultsArray = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $Master_Cod = $row["Master_Cod"];
    $Master_exe_Name = $row["Master_exe_Name"];
  
    $result = getDataForCoordinator($Master_Cod, $pdo, $date);
    
    if ($result) {
        
        $month = $result['month'];
        $totalSum = $result['total_sum'];
        $year = 2023; // Set the desired year   
    } else {
        $month = 0;
        $totalSum = 0;
        $year = 2023;
    }
 
     $targetsResult = getTargetsForCoordinator($Master_Cod, $pdo, $year, $month);
   
 
    if ($targetsResult) {
        
        
        $targets = $targetsResult['targets'];
        // Create an associative array with the desired values
        $resultItem = [
            'Master_exe_Name' => $Master_exe_Name,
            'Master_Cod' => $Master_Cod,
            'highest_target_value_before12month' => $targets,
            'benchmarkTagetAchieved' => $totalSum,
        ];
        
    }
    $resultsArray[] = $resultItem;
    
}
return $resultsArray;
}

function getTargetsForCoordinator($Master_Cod, $pdo, $year, $month)
{ 
    // SQL query to retrieve targets for the specified manager in a specific year and month
    $sql = "SELECT  COALESCE(SUM(NULLIF(ctd.Sum, '')), 0) AS targets
            FROM executivecodes AS ec
            LEFT JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
            WHERE ctd.InYear = :year AND ctd.InMonth = :month AND ec.Master_Cod = :master_Cod 
            ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':master_Cod', $Master_Cod, PDO::PARAM_STR);
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->bindParam(':month', $month, PDO::PARAM_INT); // Set the desired month
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDataForCoordinator($Master_Cod, $pdo, $date)
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
            SELECT DISTINCT Sub_Code FROM executivecodes WHERE Master_Cod = :master_Cod
        )
        AND DAY(collectiondata.collectiondate) BETWEEN 1 AND $dayrange -- Change the day range as needed
        GROUP BY month
    ) AS subquery
    ORDER BY subquery.total_sum DESC
    LIMIT 1    
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':master_Cod', $Master_Cod, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
