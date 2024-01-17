<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
  $password = $_POST["password"];

  if (empty($username)) {
    die("Error: Username is required.");
  }
  if (empty($password)) {
    die("Error: Password is required.");
  }

  $mysqli = new mysqli("localhost:3306", "root", "root", "testforgym");

  if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
  }

  $stmt = $mysqli->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->bind_result($UserId, $dbUsername, $dbPassword);
  $stmt->fetch();

  if ($dbUsername && password_verify($password, $dbPassword)) {
    $stmt->close();
    $mysqli->close();
    $_SESSION['username'] = $username;
    $_SESSION['user_id'] = $UserId;
    header("Location: main.php");
    exit();
  } else {
    echo "Invalid username or password.";
  }

  // Close statement and connection (in case of an error)
  $stmt->close();
  $mysqli->close();
}
$pageTitle = "Login";

require_once 'loginheader.php';
?>
<link rel="stylesheet" href="loginstyle.css">
<div class="container">
  <h2>User Login</h2>
  <form action="" method="post">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Login</button>
  </form>
  <p>Don't have a profle?? <a href="register.php">Click here</a></p>
</div>
<?php
require_once 'footer.php';

?>