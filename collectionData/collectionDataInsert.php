<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

// Check if the table exists, and create it if it doesn't
$checkTableQuery = "SHOW TABLES LIKE 'collectiondata'";
$tableExists = $pdo->query($checkTableQuery)->rowCount() > 0;

if (!$tableExists) {
    // Create the table
    $createTableQuery = "CREATE TABLE collectiondata (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        collectiondate DATE,
        ccount INT(11),
        invoices VARCHAR(255),
        invoicescount INT(11),
        value INT(11),
        excode VARCHAR(10),
        insertExcode VARCHAR(10),
        insertedRole VARCHAR(20),
        insertTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->query($createTableQuery);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the data from the request
    $data = json_decode(file_get_contents("php://input"), true);
    $collectiondate = $data['collectiondate'];
    $ccount = $data['ccount'];
    $invoices = $data['invoices'];
    $invoicescount = $data['invoicescount'];
    $value = $data['value'];
    $excode = $data['excode'];
    $insertExcode = $data['insertExcode'];
    $insertedRole = $data['insertedRole'];

    // Check if a row with the same excode and the desired date already exists
    $checkExcodeQuery = "SELECT COUNT(*) FROM collectiondata WHERE excode = ? AND collectiondate = ?";
    $stmt = $pdo->prepare($checkExcodeQuery);
    $stmt->execute([$excode, $collectiondate]);
    $excodeExists = $stmt->fetchColumn() > 0;

    if (!$excodeExists) {
        // Prepare the SQL statement for insertion
        $sql = "INSERT INTO collectiondata (collectiondate, ccount, invoices, invoicescount, value, excode, insertExcode, insertedRole)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        // Execute the SQL statement for insertion
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$collectiondate, $ccount, $invoices, $invoicescount, $value, $excode, $insertExcode, $insertedRole]);

        if ($stmt->rowCount() > 0) {
            $response = array("success" => true, "message" => "Insertion successful");
            echo json_encode($response);
        } else {
            $response = array("success" => false, "message" => "Insertion failed");
            echo json_encode($response);
        }
    } else {
        // Prepare the SQL statement for update
        $sql = "UPDATE collectiondata SET ccount = ?, invoices = ?, invoicescount = ?, value = ?, insertExcode = ?, insertedRole = ?, insertTime = CURRENT_TIMESTAMP WHERE excode = ? AND collectiondate = ?";

        // Execute the SQL statement for update
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ccount, $invoices, $invoicescount, $value, $insertExcode, $insertedRole, $excode, $collectiondate]);

        if ($stmt->rowCount() > 0) {
            $response = array("success" => true, "message" => "Update successful");
            echo json_encode($response);
        } else {
            $response = array("success" => false, "message" => "Update failed");
            echo json_encode($response);
        }
    }
}
?>
