<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('db-config.php');
require_once('tokenGet/vendor/autoload.php'); // Include the JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$role = $_POST['role'];
$code = $_POST['code'];

// Prepare the SQL query to fetch the dropdown options based on the role and code
$query = "SELECT option_column FROM dropdown_table WHERE role = '$role' AND code = '$code'";

// Execute the query
$result = mysqli_query($connection, $query);

// Check if the query was successful
if ($result) {
  $options = array();

  // Fetch the options from the result set
  while ($row = mysqli_fetch_assoc($result)) {
    $options[] = $row['option_column'];
  }

  // Return the options as a JSON response
  echo json_encode($options);
} else {
  // Handle the case when the query fails
  echo "Error: " . mysqli_error($connection);
}

// Close the MySQL connection
mysqli_close($connection);
?>
