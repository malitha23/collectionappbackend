<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../../db-config.php');

// Prepare the SQL statement
$sql = "SELECT `subcategory` FROM `complaintssubcategory` WHERE `category` = 'CoolerComplain'";
$stmt = $pdo->prepare($sql);

// Initialize options array
$options = [];

// Execute the prepared statement
$stmt->execute();

// Fetch the results
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $options[] = array(
        'subcategory' => $row['subcategory']
    );
}

// Return the response
echo json_encode($options);
?>
