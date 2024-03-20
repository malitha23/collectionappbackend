<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('db-config.php');
require_once('tokenGet/vendor/autoload.php'); // Include the JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Create the users table if it doesn't exist
$createTableQuery = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        option VARCHAR(255) NOT NULL,
        executivecode VARCHAR(255) NOT NULL,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL
    )
";
$createTableStmt = $pdo->prepare($createTableQuery);
$createTableStmt->execute();

// Get the request payload
$requestPayload = file_get_contents("php://input");
$registrationData = json_decode($requestPayload);

// Extract the registration data
$selectedOption = $registrationData->option;
$executivecode = $registrationData->executivecode;
$username = $registrationData->username;
$email = $registrationData->email;
$password = $registrationData->password;

// Validate the registration data (add your own validation logic here)
if (empty($selectedOption) || empty($executivecode) || empty($username)  || empty($password)) {
    // Return an error response if any field is empty
    $response = array("success" => false, "message" => "All fields are required");
    echo json_encode($response);
    exit;
}

try {
   if ($selectedOption == "Manager") {
    $roleColumn = "Mangers";
} else if ($selectedOption == "Coordinator") {
    $roleColumn = "Coordinator";
} else {
    $roleColumn = "Master_Code";
}



if ($roleColumn == "Master_Code") {
   
    $roleColumn = "Master_Cod";
    
    $roleCheckQuery = "SELECT * FROM executivecodes WHERE $roleColumn = :executivecode";
    // Check if the executive code exists
    $roleCheckStmt = $pdo->prepare($roleCheckQuery);
    $roleCheckStmt->bindParam(":executivecode", $executivecode);
    $roleCheckStmt->execute();

    if ($roleCheckStmt->rowCount() == 0) {
        
        $roleColumn = "Sub_Code";
    $roleCheckQuery = "SELECT * FROM executivecodes WHERE $roleColumn = :executivecode";
    // Check if the executive code exists
    $roleCheckStmt = $pdo->prepare($roleCheckQuery);
    $roleCheckStmt->bindParam(":executivecode", $executivecode);
    $roleCheckStmt->execute();

    if ($roleCheckStmt->rowCount() == 0) {
      
        // Return an error response if no rows were found for the given executive code
        $response = array("success" => false, "message" => "Enter valid executive code");
        echo json_encode($response);
        exit;
        
    }else{
        $selectedOption = "Sub_Code";
    }
    }else{
        $selectedOption = "Master_Code";
    }
}else{
    
    $roleCheckQuery = "SELECT * FROM executivecodes WHERE $roleColumn = :executivecode";
// Check if the executive code exists
$roleCheckStmt = $pdo->prepare($roleCheckQuery);
$roleCheckStmt->bindParam(":executivecode", $executivecode);
$roleCheckStmt->execute();

if ($roleCheckStmt->rowCount() == 0) {
    // Return an error response if no rows were found for the given executive code
    $response = array("success" => false, "message" => "Enter valid executive code");
    echo json_encode($response);
    exit;
}
}

  // Check if the username already exists
    $usernameCheckQuery = "SELECT * FROM users WHERE username = :username";
    $usernameCheckStmt = $pdo->prepare($usernameCheckQuery);
    $usernameCheckStmt->bindParam(":username", $username);
    $usernameCheckStmt->execute();

  
    
    if ($usernameCheckStmt->rowCount() > 0) {
        // Return an error response if the username already exists
        $response = array("success" => false, "message" => "Username already exists");
        echo json_encode($response);
        exit;
    }
    if($email != ""){
          // Check if the email already exists
    $emailCheckQuery = "SELECT * FROM users WHERE email = :email";
    $emailCheckStmt = $pdo->prepare($emailCheckQuery);
    $emailCheckStmt->bindParam(":email", $email);
    $emailCheckStmt->execute();
    
    if ($emailCheckStmt->rowCount() > 0) {
        // Return an error response if the email already exists
        $response = array("success" => false, "message" => "Email already exists");
        echo json_encode($response);
        exit;
    }
    }else{
        $email ="";
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the database
    $insertQuery = "INSERT INTO users (option, executivecode, username, email, password) 
                    VALUES (:option, :executivecode, :username, :email, :password)";
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->bindParam(":option", $selectedOption);
    $insertStmt->bindParam(":executivecode", $executivecode);
    $insertStmt->bindParam(":username", $username);
    $insertStmt->bindParam(":email", $email);
    $insertStmt->bindParam(":password", $hashedPassword);
    $insertStmt->execute();

    // Generate a token for the user
    $token = generateToken($username);
    // Set the token in the response headers with a custom prefix
    header("Authorization: Token " . $token);

    // Return the token in the response
    $response = array("success" => true, "message" => "Registration successful", "token" => $token, "role" => $selectedOption, "executivecode" => $executivecode);
    echo json_encode($response);
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
