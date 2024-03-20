<?php
header('Access-Control-Allow-Origin: *'); // Allow requests from any origin
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); // Allow specific HTTP methods
header('Access-Control-Allow-Headers: Content-Type'); // Allow specific headers


require_once('db-config.php');
require_once('tokenGet/vendor/autoload.php'); // Include the JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Get the request payload
$requestPayload = file_get_contents("php://input");
$loginData = json_decode($requestPayload);

// Extract the login data
$loginIdentifier = $loginData->loginCredential;
$password = $loginData->password;

// Validate the login data (add your own validation logic here)
if (empty($loginIdentifier) || empty($password)) {
  // Return an error response if any field is empty
  $response = array("success" => false, "message" => "All fields are required");
  echo json_encode($response);
  exit;
}

try {
  // Check if the username or email exists
  $loginCheckQuery = "SELECT * FROM users WHERE username = :loginIdentifier OR email = :loginIdentifier";
  $loginCheckStmt = $pdo->prepare($loginCheckQuery);
  $loginCheckStmt->bindParam(":loginIdentifier", $loginIdentifier);
  $loginCheckStmt->execute();

  if ($loginCheckStmt->rowCount() === 1) {
    // Retrieve the user's data
    $user = $loginCheckStmt->fetch(PDO::FETCH_ASSOC);

    // Verify the password
    if (password_verify($password, $user['password'])) {
      
      $selectedOption = $user['option'];
      $executivecode = $user['executivecode'];
      // Generate a token for the user
      $token = generateToken($user['username']);

      // Set the token in the response headers with a custom prefix
      header("Authorization: Token " . $token);

      // Return the token in the response
      $response = array("success" => true, "message" => "Login successful", "token" => $token, "role" => $selectedOption, "executivecode" => $executivecode);
      echo json_encode($response);
    } else {
      // Return an error response if the password is incorrect
      $response = array("success" => false, "message" => "Incorrect password");
      echo json_encode($response);
    }
  } else {
    // Return an error response if the username or email doesn't exist
    $response = array("success" => false, "message" => "Invalid login username or email");
    echo json_encode($response);
  }
} catch (PDOException $e) {
  // Return an error response if there's a database error
  $response = array("success" => false, "message" => "Database error: " . $e->getMessage());
  echo json_encode($response);
}

function generateToken($username) {
  // Encode the username using base64
  $usernameEncoded = base64_encode($username);

  // Return the encoded username as the token
  return $usernameEncoded;
}
?>
