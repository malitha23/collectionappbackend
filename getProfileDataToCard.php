<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('db-config.php');
require_once('tokenGet/vendor/autoload.php'); // Include the JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Get the request payload
$requestPayload = file_get_contents("php://input");
$profileData = json_decode($requestPayload);

// Extract the login data
$role = $profileData->role;
$executiveCode = $profileData->executiveCode;

// Execute the appropriate SQL query based on the role
if ($role == "Manager") {
  $sql = "SELECT DISTINCT `Manger_Name` FROM `executivecodes` WHERE `Mangers` = '$executiveCode'";
} else if ($role == "Coordinator") {
  $sql = "SELECT DISTINCT `Coordinator_Name` FROM `executivecodes` WHERE `Coordinator` = '$executiveCode'";
} else if ($role == "Master_Code") {
  $sql = "SELECT DISTINCT `Master_exe_Name` FROM `executivecodes` WHERE `Master_Cod` = '$executiveCode' GROUP BY `Master_Cod`";
} else if ($role == "Sub_Code") {
  $sql = "SELECT DISTINCT `Master_exe_Name` FROM `executivecodes` WHERE `Sub_Code` = '$executiveCode'";
}

// Perform the query
if (isset($sql)) {
  $queryResult = $pdo->query($sql);

  // Check if the query was successful
  if ($queryResult) {
    // Fetch the first row result
    $row = $queryResult->fetch(PDO::FETCH_ASSOC);
    if ($row) {
              if ($role == "Manager") {
     $result = $row['Manger_Name'];
} else if ($role == "Coordinator") {
     $result = $row['Coordinator_Name'];
} else if ($role == "Master_Code") {
    $result = $row['Master_exe_Name'];
} else if ($role == "Sub_Code") {
    $result = $row['Master_exe_Name'];
}
     
    }
  } else {
    // Handle the query error
    $error = $pdo->errorInfo();
    // Log or handle the error accordingly
  }
}

// Return the result as JSON
echo json_encode($result);
?>
