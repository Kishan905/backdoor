<?php
include '/home/brainwareunivers/public_html/bwu-buis-api/config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form actions
    $action = $_POST['action'];
    $query = $_POST['query'];

    if (!empty($query)) {
        if ($connection->query($query) === TRUE) {
            echo "<p>Action '$action' executed successfully!</p>";
        } else {
            echo "<p>Error: " . $connection->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager</title>
</head>
<body>
    <h1>Database Management Interface</h1>
    <form method="POST">
        <label for="query">Enter SQL Query:</label><br>
        <textarea name="query" id="query" rows="5" cols="50"></textarea><br><br>
        <button type="submit" name="action" value="execute">Execute Query</button>
    </form>
</body>
</html>
