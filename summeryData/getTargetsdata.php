<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../db-config.php');

// Get the request payload
$requestPayload = json_decode(file_get_contents("php://input"), true); // Decode the JSON payload as an associative array

$executiveCode = $requestPayload['Executivecode'];
$role = $requestPayload['Role'];
// $month = 8;
// $executiveCode = 'EX0280';
// $role = 'Coordinator';
// $year = date('Y');

if($role == "Head"){
  $query = "SELECT DISTINCT * FROM `categorymanagers` GROUP BY `category`";
  functioncallfirstadmin($query);
}else if($role == "Manager"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = :executiveCode ";
  functioncallfirst($query);
}else if($role == "Coordinator"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = (
      SELECT DISTINCT `Mangers`
      FROM `executivecodes`
      WHERE `Coordinator` = :executiveCode
  )";
  functioncallfirst($query);
}else if($role == "Master_Code"){
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = (
      SELECT DISTINCT `Mangers`
      FROM `executivecodes`
      WHERE `Master_Cod` = :executiveCode
  )";
  functioncallfirst($query);
}else{
  $query = "SELECT `category` FROM `categorymanagers`
  WHERE `managercode` = (
      SELECT DISTINCT `Mangers`
      FROM `executivecodes`
      WHERE `Master_Cod` = :executiveCode
  )"; 
  functioncallfirst($query);
}

function functioncallfirst($query){
  global $pdo;
  global $executiveCode;
  $stmt = $pdo->prepare($query);

// Bind the value of $executiveCode to the :executiveCode placeholder
$stmt->bindParam(':executiveCode', $executiveCode, PDO::PARAM_STR);

// Execute the query
$stmt->execute();

// Fetch the result
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if there are rows
if (!empty($result)) {
  foreach ($result as $row) {
      $category = $row['category'];
     
      if($category == 'domestic' || $category == 'branch'){
        $tableName = 'targetdayrangesdomestic';
        gettabletargetdata($tableName);
      }else if($category == 'corporate'){
        $tableName = 'targetdayrangescorporate';
        gettabletargetdata($tableName);
      }else if($category == 'db'){
        $tableName = 'targetdayrangesnone';
        gettabletargetdata($tableName);
      }
  }
} else {
  echo "No categories found.";
}
}

function gettabletargetdata($tableName){
global $pdo;

// Query to select all rows from the table
$sql = "SELECT * FROM $tableName";

// Execute the query
$result = $pdo->query($sql);

// Check if the query was successful
if ($result) {
  // Array to store the rows
  $rows = array();

  // Fetch each row and add it to the array
  while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = $row;
  }

  // Close the result set
  $result->closeCursor();

  // Print the array of rows as JSON
  echo json_encode($rows);
} else {
  // Query failed
  echo 'Error: ' . $pdo->errorInfo()[2];
}

}


function functioncallfirstadmin($query){
  global $pdo;
  global $executiveCode;
  $stmt = $pdo->prepare($query);  
// Execute the query
$stmt->execute();

// Fetch the result
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if there are rows
if (!empty($result)) {
  $response = []; // Create an array to hold the response data

foreach ($result as $row) {
  $category = $row['category'];
  $managercode = $row['managercode'];

  // Define the table name based on category
  $tableName = ($category == 'domestic') ? 'targetdayrangesdomestic' : 'targetdayrangescorporate';

  // Call the function and store the result
  $targetRanges = gettabletargetdataadmin($tableName, $managercode, $category);

  // Add the result to the response array
  $response[$category] = $targetRanges;
}

// Encode the response array as JSON
echo json_encode($response);
} else {
  echo "No categories found.";
}
}

function gettabletargetdataadmin($tableName, $managercode, $category){
  global $pdo;
  
  // Query to select all rows from the table
  $sql = "SELECT * FROM $tableName";
  
  // Execute the query
  $result = $pdo->query($sql);
  
  // Check if the query was successful
  if ($result) {
    // Array to store the rows
    $rows = array();
  
    // Fetch each row and add it to the array
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $rows[] = $row;
    }
  
    // Close the result set
    $result->closeCursor();
  
    // Print the array of rows as JSON
    return $rows;
  } else {
    // Query failed
    echo 'Error: ' . $pdo->errorInfo()[2];
  }
  
  }
?>
