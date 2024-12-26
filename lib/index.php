<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=UTF-8');

// Database connection parameters
$host = "localhost";  // MySQL host
$username = "root";  // MySQL username
$password = "new_password";  // MySQL password
$database = "gps_tracking";  // Database name

// Basic error handling
try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Simple query to test
    $sql = "SELECT * FROM gps_data ORDER BY timestamp DESC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Data</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <h1>GPS Tracking Data</h1>
    
    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['latitude']); ?></td>
                    <td><?php echo htmlspecialchars($row['longitude']); ?></td>
                    <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

</body>
</html>

<?php
    // Close connection
    $conn->close();
    
} catch (Exception $e) {
    // Display error message
    echo "<div style='color: red; padding: 20px;'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

