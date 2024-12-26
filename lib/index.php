<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "new_password";
$database = "gps_tracking";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination settings
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Sorting settings
$order = isset($_GET['order']) ? $_GET['order'] : 'timestamp';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'DESC';

// Fetch total records for pagination
$total_result = $conn->query("SELECT COUNT(*) AS total FROM gps_data");
$total = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Fetch data
$sql = "SELECT * FROM gps_data ORDER BY $order $sort LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Handle delete request
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM gps_data WHERE id = $id");
    header("Location: index.php"); // Redirect to avoid resubmission
}

// Handle multiple delete request
if (isset($_POST['delete_selected'])) {
    $selected_ids = $_POST['selected_ids'];
    foreach ($selected_ids as $id) {
        $conn->query("DELETE FROM gps_data WHERE id = $id");
    }
    header("Location: index.php"); // Redirect to avoid resubmission
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Tracking Data</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .wrapper {
            min-height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
        }
        .pagination-container {
            margin-top: 20px;
            padding: 15px 0;
            background-color: white;
        }
        table {
            width: 1200px;
            border-collapse: collapse;
            margin: 0 auto;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        th {
            background-color: #4CAF50;
            color: white !important;
        }
        th a {
            color: white !important;
            text-decoration: none;
        }
        .no-column {
            width: 40px;
        }
        .select-column {
            width: 100px;
        }
        .button-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="container mt-5">
                <h1>GPS Tracking Data</h1>
                <form id="deleteForm" method="post">
                    <div class="button-container">
                        <button type="submit" name="delete_selected" id="deleteSelectedBtn" class="btn btn-secondary" disabled onclick="return confirm('Are you sure you want to delete selected entries?');">Delete Selected</button>
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="no-column">No.</th>
                                <th><a href="?order=user_id&sort=<?php echo $sort === 'ASC' ? 'DESC' : 'ASC'; ?>">User ID</a></th>
                                <th><a href="?order=latitude&sort=<?php echo $sort === 'ASC' ? 'DESC' : 'ASC'; ?>">Latitude</a></th>
                                <th><a href="?order=longitude&sort=<?php echo $sort === 'ASC' ? 'DESC' : 'ASC'; ?>">Longitude</a></th>
                                <th><a href="?order=timestamp&sort=<?php echo $sort === 'ASC' ? 'DESC' : 'ASC'; ?>">Timestamp</a></th>
                                <th class="select-column"><input type="checkbox" id="selectAll"> Select All</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result->num_rows > 0) {
                                $counter = $offset + 1;
                                while($row = $result->fetch_assoc()) { 
                            ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['latitude']); ?></td>
                                        <td><?php echo htmlspecialchars($row['longitude']); ?></td>
                                        <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                                        <td>
                                            <input type="checkbox" name="selected_ids[]" value="<?php echo $row['id']; ?>">
                                        </td>
                                    </tr>
                            <?php 
                                }
                            } else {
                            ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No Data Found</td>
                                    </tr>
                            <?php 
                            }
                            ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        
        <div class="pagination-container">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&order=<?php echo $order; ?>&sort=<?php echo $sort; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Function to update delete button state
            function updateDeleteButtonState() {
                var checkedBoxes = $('input[name="selected_ids[]"]:checked').length;
                var deleteButton = $('#deleteSelectedBtn');
                
                if (checkedBoxes > 0) {
                    deleteButton.removeClass('btn-secondary').addClass('btn-danger');
                    deleteButton.prop('disabled', false);
                } else {
                    deleteButton.removeClass('btn-danger').addClass('btn-secondary');
                    deleteButton.prop('disabled', true);
                }
            }

            // Handle select all checkbox
            $('#selectAll').click(function() {
                $('input[name="selected_ids[]"]').prop('checked', this.checked);
                updateDeleteButtonState();
            });

            // Handle individual checkboxes
            $('input[name="selected_ids[]"]').click(function() {
                var allChecked = $('input[name="selected_ids[]"]').length === $('input[name="selected_ids[]"]:checked').length;
                $('#selectAll').prop('checked', allChecked);
                updateDeleteButtonState();
            });

            // Initialize button state
            updateDeleteButtonState();
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>