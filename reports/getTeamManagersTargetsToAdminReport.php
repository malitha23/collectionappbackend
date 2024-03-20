<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php'); // Make sure this file exists and contains the database connection code

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
$dayrange = $data['dayrange'];
$month = $data['month'];
$year = $data['year'];
// $dayrange = 30;
// $month = 8; // Convert month name to numeric value
// $year = 2023;

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
                'Manager' => $ManagerCode,
                'ManagerName' => $ManagerName,
                '0TO31' => 0,
                '31TO60' => 0,
                '61TO90' => 0,
                '91TO120' => 0,
                'OVER120' => 0,
                'targets' => 0,
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
                           FROM `ageingreporttargets`
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
                $sums['targets'] += floatval($result2['TOTOUTS']);
                
            }
            $MangersData[] = $sums;
        } 
        $jsonResult = json_encode($MangersData);
        
        // Send the JSON response
        header('Content-Type: application/json');
        echo $jsonResult;

?>

