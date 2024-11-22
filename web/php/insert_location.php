<?php
header("Content-Type: application/json");
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gps_data";
$port = 3307;

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur'); // Set the timezone to Asia/Kuala_Lumpur

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data["user_id"];
$latitude = $data["latitude"];
$longitude = $data["longitude"];
$timestamp = date("Y-m-d H:i:s"); // Store the current timestamp

// Insert new location without checking for duplicates
$stmt = $conn->prepare("INSERT INTO locations (user_id, latitude, longitude, timestamp) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sdds", $user_id, $latitude, $longitude, $timestamp);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Data inserted successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
