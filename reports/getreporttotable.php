<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

// Assuming you have already established a database connection

// Get the request payload
$requestPayload = file_get_contents("php://input");
$monthData = json_decode($requestPayload);

// Extract the login data
$fromdate = $monthData->fromdate; // Assuming you pass the month value as a query parameter
$todate = $monthData->todate;
$selectedExecutivecode = $monthData->selectedExecutivecode;
$year = date('Y'); // Get the current year
$startDate = $fromdate;
$endDate = $todate; // Assuming maximum of 31 days in a month

// Prepare the SQL query
$query = "SELECT * FROM collectiondata WHERE excode = '$selectedExecutivecode' AND collectiondate BETWEEN :startDate AND :endDate";
$statement = $pdo->prepare($query);
$statement->bindParam(':startDate', $startDate);
$statement->bindParam(':endDate', $endDate);

// Execute the query
if ($statement->execute()) {
    // Check if any rows were returned
    if ($statement->rowCount() > 0) {
        $data = array(); // Array to store the fetched rows

        // Fetch the data row by row
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            // Access each column value of the row
            $collectionDate = $row['collectiondate'];
            $ccount = $row['ccount'];
            $invoices = $row['invoices'];
            $invoicesCountValue = $row['invoicescount'];
            $value = "Rs. " . $row['value']; 
            $insertedRole = $row['insertedRole'];
            $insertTime = $row['insertTime'];

            if ($insertedRole == "Sub_Code") {
                $insertedRole = "C-Executive";
            } else if ($insertedRole == "Master_Code") {
                $insertedRole = "C-Executive-Head";
            }

            // Create an associative array for the row
            $rowArray = array(
                'collectiondate' => $collectionDate,
                'ccount' => $ccount,
                'invoices' => $invoices,
                'invoicesCountValue' => $invoicesCountValue,
                'value' => $value,
                'insertedRole' => $insertedRole,
                'insertTime' => $insertTime
            );

            // Add the row array to the data array
            $data[] = $rowArray;
        }

        // Return the data as JSON response
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        // No rows found
        $data = array(
            'collectiondate' => "",
            'ccount' => "",
            'invoices' => "",
            'invoicesCountValue' => "No data found",
            'value' => "",
            'insertedRole' => "",
            'insertTime' => ""
        );

        // Return the data as JSON response
        header('Content-Type: application/json');
        echo json_encode(array($data));
    }
} else {
    // Handle query execution error
    $error = $statement->errorInfo();
    echo "Error executing query: " . $error[2];
}

?>
