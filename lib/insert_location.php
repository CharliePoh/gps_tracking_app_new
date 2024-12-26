<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all requests for debugging
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

// Set headers for CORS and JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Log raw input
$raw_input = file_get_contents('php://input');
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Raw input: " . $raw_input . "\n", FILE_APPEND);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection parameters
$host = "localhost";
$username = "root";
$password = "new_password";
$database = "gps_tracking";

try {
    // Log connection attempt
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Attempting database connection\n", FILE_APPEND);
    
    // Create connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Log successful connection
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Database connected successfully\n", FILE_APPEND);

    // Parse JSON input
    $data = json_decode($raw_input, true);
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);

    if (!isset($data['latitude']) || !isset($data['longitude'])) {
        throw new Exception("Missing required fields");
    }

    $latitude = $data['latitude'];
    $longitude = $data['longitude'];
    
    // Log values
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Lat: $latitude, Long: $longitude\n", FILE_APPEND);
    
    // Prepare SQL statement
    $sql = "INSERT INTO gps_data (latitude, longitude, timestamp) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters
    if (!$stmt->bind_param("dd", $latitude, $longitude)) {
        throw new Exception("Binding parameters failed: " . $stmt->error);
    }
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Log success
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Data inserted successfully\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Location data saved successfully'
    ]);
    
} catch (Exception $e) {
    // Log error
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>

