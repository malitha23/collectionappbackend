<?php
header('Access-Control-Allow-Origin: *'); // Replace with the actual origin of your Angular application
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once('db-config.php');
require_once('tokenGet/vendor/autoload.php'); // Include the JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'];


// Validate the token
$valid = validateToken($token);

// Return the validation result
$response = array("valid" => $valid);
echo json_encode($response);

// Helper function to extract the token from the Authorization header
function extractTokenFromHeader($header) {
  // Split the header value by space
  $headerParts = explode(' ', $header);

  // Check if the header format is correct
  if (count($headerParts) === 2 && $headerParts[0] === 'Bearer') {
    return $headerParts[1]; // Return the token part
  }

  return null; // Return null if header format is incorrect
}

// Helper function to validate the token against the database
function validateToken($token) {
   $decodedUsername = decodeToken($token);

if ($decodedUsername) {
  $usernameExists = findUsernameInTable($decodedUsername);
  if ($usernameExists) {
    return true;
  } else {
    return false;
  }
} else {
   return false;
} 
}
function decodeToken($token) {
  // Decode the token by base64 decoding the username
  $decodedUsername = base64_decode($token);

  // Return the decoded username
  return $decodedUsername;
}

function findUsernameInTable($username) {
  global $pdo; // Access the global $pdo object

  // Prepare the query to find the username in the table
  $query = "SELECT * FROM users WHERE username = :username";
  $stmt = $pdo->prepare($query);
  $stmt->bindParam(":username", $username);
  $stmt->execute();

  // Return true if the username exists in the table, false otherwise
  return $stmt->rowCount() > 0;
}

?>
