<?php
// Include the Database class from its directory
require_once 'Database.php';

$db = new Database();
$result_message = "";
$result_class = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_choice = $_POST['database'];
    $query = trim($_POST['query']);

    // Select database connection
    if ($db_choice === 'academics') {
        $connection = $db->getSQLIConnection();
    } elseif ($db_choice === 'mentor') {
        $connection = $db->getSQLIConnection_mentor();
    } else {
        $result_message = "Invalid database selected.";
        $result_class = "error";
        $connection = null;
    }

    // Execute query if connection is valid
    if ($connection && !empty($query)) {
        if ($connection->query($query) === TRUE) {
            $result_message = "Query executed successfully!";
            $result_class = "success";
        } else {
            $result_message = "Error: " . $connection->error;
            $result_class = "error";
        }
        $connection->close();
    } elseif (!$connection) {
        $result_message = "Failed to connect to the selected database.";
        $result_class = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 1em 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        textarea {
            width: 100%;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-size: 16px;
            resize: none;
        }
        button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header>
        <h1>Database Management Interface</h1>
    </header>
    <div class="container">
        <?php if (!empty($result_message)): ?>
            <div class="result <?= $result_class; ?>">
                <?= $result_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label for="database"><strong>Select Database:</strong></label><br>
            <select name="database" id="database">
                <option value="academics">bwuniver_academics</option>
                <option value="mentor">bwuniver_mentor</option>
            </select>

            <label for="query"><strong>Enter SQL Query:</strong></label><br>
            <textarea name="query" id="query" placeholder="Write your SQL query here..."></textarea><br>
            
            <button type="submit">Execute Query</button>
        </form>
    </div>
</body>
</html>
