<?php
// Database credentials
$host = "172.16.144.197";
$user = "root";      // Replace with your database username
$password = "";      // Replace with your database password
$dbname = "bwuniver_academics";  // Replace with your database name

// Connection to the database
$connection = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$result_message = "";
$query_result = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = trim($_POST['query']);

    if (!empty($query)) {
        $query_result = $connection->query($query);

        if ($query_result === TRUE) {
            $result_message = "Query executed successfully!";
        } elseif ($query_result === FALSE) {
            $result_message = "Error: " . $connection->error;
        }
    } else {
        $result_message = "Please enter a query.";
    }
}

// Close connection when done
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Database Query</title>
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
            height: 100px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <header>
        <h1>Test Database Query Interface</h1>
    </header>
    <div class="container">
        <form method="POST">
            <label for="query"><strong>Enter SQL Query:</strong></label><br>
            <textarea name="query" id="query" placeholder="Write your SQL query here..."></textarea><br>
            <button type="submit">Execute Query</button>
        </form>

        <?php if (!empty($result_message)): ?>
            <div class="result <?= $query_result ? 'success' : 'error'; ?>">
                <?= $result_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($query_result && $query_result instanceof mysqli_result): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($query_result->fetch_fields() as $field): ?>
                            <th><?= $field->name; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $query_result->fetch_assoc()): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= htmlspecialchars($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
