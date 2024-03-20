<?php
$host = "localhost";
$dbName = "durakathanapala_collection";
$dbUsername = "root";
$dbPassword = "";

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbName", $dbUsername, $dbPassword);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  // Handle database connection error
  die("Database connection failed: " . $e->getMessage());
}
?>