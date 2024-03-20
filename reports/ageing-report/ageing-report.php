<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../../db-config.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Main code
$requestPayload = json_decode(file_get_contents("php://input"), true);


// $executiveCode = 'EX0057';
// $role = 'Master_Code';
$executiveCode = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];

switch ($role) {
    case "Head":

        $queryManagers = "SELECT DISTINCT `Mangers`, `Manger_Name`
        FROM `executivecodes` WHERE `Mangers` != 'PO001'";

        // Prepare and execute the query to get Coordinator codes
        $stmtCoordinator = $pdo->prepare($queryManagers);
        $stmtCoordinator->execute();

        

        $MangersData = [];

        while ($rowCoordinator = $stmtCoordinator->fetch(PDO::FETCH_ASSOC)) {
            $ManagerCode = $rowCoordinator['Mangers'];
            $ManagerName = $rowCoordinator['Manger_Name'];
            
            $sums = [
                'Manger' => $ManagerCode,
                'Manger_Name' => $ManagerName,
                '0TO31' => 0,
                '31TO60' => 0,
                '61TO90' => 0,
                '91TO120' => 0,
                'OVER120' => 0,
                'TOTOUTS' => 0,
                'role' => 'mangers',
            ];
        
            // Use the Coordinator code in your $query1 to fetch data
            $query1 = "SELECT DISTINCT `Master_Cod`
                       FROM `executivecodes`
                       WHERE `Mangers` = :Mangercode
                       GROUP BY `Master_Cod`";
        
            // Prepare and execute the first query
            $stmt1 = $pdo->prepare($query1);
            $stmt1->bindParam(':Mangercode', $ManagerCode, PDO::PARAM_STR);
            $stmt1->execute();
        
            while ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                $executiveCode = $row1['Master_Cod'];
        
                // Second query for each Master_Cod
                $query2 = "SELECT `ExecutiveName`, `ExecutiveCode`,
                                  SUM(`0TO31`) AS 0TO31, SUM(`31TO60`) AS 31TO60,
                                  SUM(`61TO90`) AS 61TO90, SUM(`91TO120`) AS 91TO120,
                                  SUM(`OVER120`) AS OVER120, SUM(`TOTOUTS`) AS TOTOUTS
                           FROM `ageingreport`
                           WHERE `ExecutiveCode` IN (
                               SELECT `Sub_Code` FROM `executivecodes` WHERE `Master_Cod` = :executiveCode
                           )";
        
                // Prepare and execute the second query
                $stmt2 = $pdo->prepare($query2);
                $stmt2->bindParam(':executiveCode', $executiveCode, PDO::PARAM_STR);
                $stmt2->execute();
        
                // Fetch the result as an associative array
                $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        
                $sums['0TO31'] += floatval($result2['0TO31']);
                $sums['31TO60'] += floatval($result2['31TO60']);
                $sums['61TO90'] += floatval($result2['61TO90']);
                $sums['91TO120'] += floatval($result2['91TO120']);
                $sums['OVER120'] += floatval($result2['OVER120']);
                $sums['TOTOUTS'] += floatval($result2['TOTOUTS']);
                
            }
            $MangersData[] = $sums;
        } 
        $jsonResult = json_encode($MangersData);
        
        // Send the JSON response
        header('Content-Type: application/json');
        echo $jsonResult;
          break;
  

    case "Manager":
        // Query to get distinct Coordinator codes where Managers = 'BX002'
        $queryCoordinator = "SELECT DISTINCT `Coordinator`, `Coordinator_Name`
        FROM `executivecodes`
        WHERE `Mangers` = :executiveCode
        GROUP BY `Coordinator`";

        // Prepare and execute the query to get Coordinator codes
        $stmtCoordinator = $pdo->prepare($queryCoordinator);
        $stmtCoordinator->bindParam(':executiveCode', $executiveCode, PDO::PARAM_STR);
        $stmtCoordinator->execute();

        

        $coordinatorData = [];

        while ($rowCoordinator = $stmtCoordinator->fetch(PDO::FETCH_ASSOC)) {
            $coordinatorCode = $rowCoordinator['Coordinator'];
            $coordinatorName = $rowCoordinator['Coordinator_Name'];
        
            // Initialize sums for the coordinator along with Coordinator and Coordinator_Name
            $sums = [
                'Coordinator' => $coordinatorCode,
                'Coordinator_Name' => $coordinatorName,
                '0TO31' => 0,
                '31TO60' => 0,
                '61TO90' => 0,
                '91TO120' => 0,
                'OVER120' => 0,
                'TOTOUTS' => 0,
                'role' => 'coordinator',
            ];
        
            // Use the Coordinator code in your $query1 to fetch data
            $query1 = "SELECT DISTINCT `Master_Cod`
                       FROM `executivecodes`
                       WHERE `Coordinator` = :coordinatorCode
                       GROUP BY `Master_Cod`";
        
            // Prepare and execute the first query
            $stmt1 = $pdo->prepare($query1);
            $stmt1->bindParam(':coordinatorCode', $coordinatorCode, PDO::PARAM_STR);
            $stmt1->execute();
        
            while ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                $executiveCode = $row1['Master_Cod'];
        
                // Second query for each Master_Cod
                $query2 = "SELECT `ExecutiveName`, `ExecutiveCode`,
                                  SUM(`0TO31`) AS 0TO31, SUM(`31TO60`) AS 31TO60,
                                  SUM(`61TO90`) AS 61TO90, SUM(`91TO120`) AS 91TO120,
                                  SUM(`OVER120`) AS OVER120, SUM(`TOTOUTS`) AS TOTOUTS
                           FROM `ageingreport`
                           WHERE `ExecutiveCode` IN (
                               SELECT `Sub_Code` FROM `executivecodes` WHERE `Master_Cod` = :executiveCode
                           )";
        
                // Prepare and execute the second query
                $stmt2 = $pdo->prepare($query2);
                $stmt2->bindParam(':executiveCode', $executiveCode, PDO::PARAM_STR);
                $stmt2->execute();
        
                // Fetch the result as an associative array
                $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        
                $sums['0TO31'] += floatval($result2['0TO31']);
                $sums['31TO60'] += floatval($result2['31TO60']);
                $sums['61TO90'] += floatval($result2['61TO90']);
                $sums['91TO120'] += floatval($result2['91TO120']);
                $sums['OVER120'] += floatval($result2['OVER120']);
                $sums['TOTOUTS'] += floatval($result2['TOTOUTS']);
            }
        
            // Add the coordinator's sums to the result
            $coordinatorData[] = $sums; 
        }
        
        $jsonResult = json_encode($coordinatorData);
        
        // Send the JSON response
        header('Content-Type: application/json');
        echo $jsonResult;
        
        break;

    case "Coordinator":
        $query1 = "SELECT DISTINCT `Master_Cod`
                  FROM `executivecodes`
                  WHERE `Coordinator` = '$executiveCode'
                  GROUP BY `Master_Cod`";
        getCoordinatorViseEachMasterData($query1, $pdo);
        break;

    case "Master_Code":
        $query = "SELECT `ExecutiveCode`, `ExecutiveName`,
        SUM(`0TO31`) AS 0TO31, SUM(`31TO60`) AS 31TO60,
        SUM(`61TO90`) AS 61TO90, SUM(`91TO120`) AS 91TO120,
        SUM(`OVER120`) AS OVER120, SUM(`TOTOUTS`) AS TOTOUTS
 FROM `ageingreport`
 WHERE `ExecutiveCode` IN (
     SELECT `Sub_Code` FROM `executivecodes` WHERE `Master_Cod` = :executiveCode
 )";
        masterViseGetData($query, $pdo, $executiveCode);
        break;

    default:
        // Handle unknown roles if needed
        break;
}

// Close the database connection
$pdo = null;
   function getCoordinatorViseEachMasterData($query1, $pdo){

    // Prepare and execute the first query
    $stmt1 = $pdo->prepare($query1);
    $stmt1->execute();

    // Initialize an array to store results
    $results = [];

    while ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        $executiveCode = $row1['Master_Cod'];
        
        $query = "SELECT * FROM ageingreporttargets WHERE ExecutiveCode = :value";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':value', $executiveCode, PDO::PARAM_STR);
        $stmt->execute();
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($result){
        // Second query for each Master_Cod
        $query2 = "SELECT  `ExecutiveCode`,`ExecutiveName`,
                          SUM(`0TO31`) AS 0TO31, SUM(`31TO60`) AS 31TO60,
                          SUM(`61TO90`) AS 61TO90, SUM(`91TO120`) AS 91TO120,
                          SUM(`OVER120`) AS OVER120, SUM(`TOTOUTS`) AS TOTOUTS
                   FROM `ageingreport`
                   WHERE `ExecutiveCode` IN (
                       SELECT `Sub_Code` FROM `executivecodes` WHERE `Master_Cod` = :executiveCode
                   )";
    
        // Prepare and execute the second query
        $stmt2 = $pdo->prepare($query2);
        $stmt2->bindParam(':executiveCode', $executiveCode, PDO::PARAM_STR);
        $stmt2->execute();
    
        // Fetch the result as an associative array
        $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $jsonResult = json_encode($result2);

        // Send the JSON response
   
        // Add 'role' => 'coordinator' to the result
        $result2['role'] = 'Master_Cod';
        
        // Check if any of the selected columns have null values
        $isNull = false;
       
        foreach ($result2 as $value) {
            if ($value === null) {
                $isNull = true;
                break;
            }
        }
        
   
            $results[] = $result2;
        
        
        
    }
   }
    $jsonResult = json_encode($results);

    // Send the JSON response
    header('Content-Type: application/json');
    echo $jsonResult;
   }
   
   
   function masterViseGetData($query, $pdo, $executiveCode){
    // Prepare and execute the query
    $results = [];
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':executiveCode', $executiveCode, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the result as an associative array
    $results = $stmt->fetch(PDO::FETCH_ASSOC);
    $results['role'] = "Sub_Cod"; 
    $results2[] = $results;
    // Close the database connection
    $pdo = null;

    // Convert the result to a JSON array
    $jsonResult = json_encode($results2);

    // Send the JSON response
    header('Content-Type: application/json');
    echo $jsonResult; 
}

?>
