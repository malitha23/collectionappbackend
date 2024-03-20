<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../../db-config.php'); // Make sure this file exists and contains the database connection code

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
// $dayrange = $data['dayrange'];
// $month = $data['month'];
// $year = $data['year'];
$dayrange = $data['dayrange'];
$month = $data['month'];
$year = $data['year'];
// Prepare the SQL statement using a parameterized query
$sql = "SELECT ec.Manger_Name, ec.Mangers, SUM(ctd.Sum) AS targets 
        FROM executivecodes AS ec 
        INNER JOIN collectionexectivetargetdata AS ctd ON ctd.CollectionExective = ec.Sub_Code
        WHERE ctd.InYear = :year AND ctd.InMonth = :month
        GROUP BY ec.Mangers";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':year', $year, PDO::PARAM_INT);
$stmt->bindParam(':month', $month, PDO::PARAM_INT);
$stmt->execute();

// Fetch the results
$options = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $managerCode =  $row['Mangers'];
    $managerName =  $row['Manger_Name'];
    $targets =  $row['targets'];
    
    $getCommitmentTargets =  getCommitmentTarget($managerCode, $pdo);    
    $calculateCommitmentTargets = calculateCommitmentTargets($getCommitmentTargets, $targets);
    $options[] = array(
     'ManagerName' => $managerName,
     'Manager' => $managerCode,
     'Commitmenttargets' => $calculateCommitmentTargets,
     'CommitmentPercentage' => $getCommitmentTargets
    );
}

function getCommitmentTarget($managerCode, $pdo){
    $sql = "SELECT `targetPercentage` FROM `commitmenttargettoteam` WHERE `managercode` = :managercode";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':managercode', $managerCode, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) {
        // Check if the results array is not empty
        $targetPercentage = $results[0]['targetPercentage'];
        return $targetPercentage;
    } else {
        // Handle the case where no results were found
        return 0; // Or any other appropriate value
    }
}


function calculateCommitmentTargets($getCommitmentTargets, $targets){
  $comTarget = ($targets/100)*$getCommitmentTargets;
  return $comTarget;
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($options);
?>
