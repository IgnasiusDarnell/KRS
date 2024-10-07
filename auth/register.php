<?php
require_once '../conn.php';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $status = "mhs";
    $password = trim($_POST['password']);

    $hashedPassword = hash('md5', $password);

    $conn = getDbConnection();

    $stmt = $conn->prepare("INSERT INTO user (username, status, password) VALUES (?, ?, ?)");

    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param('sss', $username, $status, $hashedPassword);
    if ($stmt->execute()) {
        echo "Registration successful!";
        header('Location: index.php'); 
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body class="container mt-5">
    <h2>Register</h2>
    <form method="POST" action="register.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" name="register" class="btn btn-primary">Register</button>
    </form>
</body>

</html>