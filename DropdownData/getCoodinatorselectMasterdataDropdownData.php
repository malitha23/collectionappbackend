<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once('../db-config.php');

// Retrieve the data from the request
$data = json_decode(file_get_contents("php://input"), true);
$role = $data['role'];
$executiveCode = $data['code'];

$currentYear = date('Y');
$currentMonth = date('m');
$currentMonth = ltrim($currentMonth, '0'); 

// select jiffry team prasannan distributors
if( $executiveCode == "EX1070"){
    $sql = "SELECT `Master_exe_Name`,`Master_Cod` FROM `executivecodes` WHERE Master_Cod = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$executiveCode]);

    // Initialize options array
    $options = [];

    // Fetch the results
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    
        // If there are results, add data to options
        $options[] = array(
            'masterExeName' => $row['Master_exe_Name'],
            'subCode' => $row['Master_Cod']
        );
    
    }
}else{
    $sql = "SELECT `Master_exe_Name`,`Sub_Code` FROM `executivecodes` WHERE Sub_Code = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$executiveCode]);

    // Initialize options array
    $options = [];

    // Fetch the results
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    
        // If there are results, add data to options
        $options[] = array(
            'masterExeName' => $row['Master_exe_Name'],
            'subCode' => $row['Sub_Code']
        );
    
    }
}



// Return the response
echo json_encode($options);
?>
