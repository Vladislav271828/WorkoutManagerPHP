<?php

require_once 'auth.php';
checkAuthentication();

require_once 'baza.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_trainer"])) {
  $trainerId = filter_var($_POST["trainer_id"], FILTER_VALIDATE_INT);

  if ($trainerId === false) {
      echo "Invalid trainer ID";
      exit();
  }

  $user_id = $_SESSION['user_id'];

  $stmt = $mysqli->prepare("SELECT 1 FROM trainers WHERE trainer_id = ? AND user_id = ?");
  $stmt->bind_param("ii", $trainerId, $user_id);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
      $stmt = $mysqli->prepare("DELETE FROM trainers WHERE trainer_id = ?");
      $stmt->bind_param("i", $trainerId);

      if ($stmt->execute()) {
          header("Location: trainers.php");
          exit();
      } else {
          echo "Error: " . $stmt->error;
      }
  } else {
      echo "Trainer not found or does not belong to the current user.";
  }

  $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_trainer"])) {
  $trainerName = filter_var($_POST["trainer_name"], FILTER_SANITIZE_STRING);
  $phoneNumber = filter_var($_POST["phone_number"], FILTER_SANITIZE_STRING);

  $stmt = $mysqli->prepare("INSERT INTO trainers (name, phone_number, user_id) VALUES (?, ?, ?)");
  $stmt->bind_param("ssi", $trainerName, $phoneNumber, $_SESSION['user_id']);

  if ($stmt->execute()) {
    header("Location: trainers.php");
    exit();
  } else {
    echo "Error: " . $stmt->error;
  }

  $stmt->close();
}

$result = $mysqli->query("SELECT trainer_id, name, phone_number, user_id FROM trainers");

if (!$result) {
  die("Error: " . $mysqli->error);
}

$user_id = $_SESSION['user_id'];
$pageTitle = "Trainers";
require_once 'header.php';


?>

<form action="" method="post" class="add-trainer-form">
  <label for="trainer_name">Trainer Name:</label>
  <input type="text" id="trainer_name" name="trainer_name" required>
  <label for="phone_number">Phone Number:</label>
  <input type="text" id="phone_number" name="phone_number">
  <button type="submit" name="add_trainer">Add Trainer</button>
</form>

<h2>List of Trainers</h2>

<ul class="trainers-list">
  <?php while ($row = $result->fetch_assoc()): ?>
    <li>
      <?php echo htmlspecialchars($row['name']); ?>
      <span class="phone-number">
        <br>
        <?php
        if (!empty($row['phone_number'])) {
          echo htmlspecialchars("Phone Number: " . $row['phone_number']);
        }
        ?>
        <?php if ($user_id == $row['user_id']): ?>
          <form action="trainers.php" method="post" class="delete-form">
            <input type="hidden" name="trainer_id" value="<?php echo $row['trainer_id']; ?>">
            <button type="submit" name="delete_trainer">Delete</button>
          </form>
        <?php endif; ?>

      </span>
    </li>
  <?php endwhile; ?>
</ul>


<?php
require_once 'footer.html';

$result->close();
$mysqli->close();
?>