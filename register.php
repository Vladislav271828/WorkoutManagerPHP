<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    if (empty($username)) {
        die("Error: Username is required.");
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Error: Please enter a valid email address.");
    }
    if (strlen($password) < 6) {
        die("Error: Password must be at least 6 characters long.");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $mysqli = new mysqli("localhost:3306", "root", "root", "testforgym");

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $mysqli->insert_id;
        $mysqli->close();
        header("Location: main.php");

        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $mysqli->close();
}
$pageTitle = "Register";

require_once 'loginheader.php';
?>
<link rel="stylesheet" href="loginstyle.css">
<div class="container">
    <h2>User Registration</h2>
    <form action="register.php" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Register</button>
    </form>
    <p>Already have a profle?? <a href="login.php">Click here</a></p>
</div>

<?php
require_once 'footer.php';
?>