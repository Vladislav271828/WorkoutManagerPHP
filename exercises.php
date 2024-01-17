<?php

require_once 'auth.php';

checkAuthentication();

require_once 'baza.php';

$pageTitle = "Exercises";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_exercise"])) {
  $exerciseIdToDelete = filter_var($_POST["exercise_id"], FILTER_VALIDATE_INT);

  

  if ($exerciseIdToDelete == true) {

      $stmtCheckReferences = $mysqli->prepare("SELECT COUNT(*) FROM workout_exercises WHERE exercise_id = ?");
      $stmtCheckReferences->bind_param("i", $exerciseIdToDelete);
      $stmtCheckReferences->execute();
      $stmtCheckReferences->bind_result($referencesCount);
      $stmtCheckReferences->fetch();
      $stmtCheckReferences->close();

      if ($referencesCount > 0) {
          $errorMessege = "This exercise is already used in a workout. Please remove it from the workout first.";
          goto error;
      }
    $stmtSelect = $mysqli->prepare("SELECT image_url FROM exercises WHERE exercise_id = ?");
    $stmtSelect->bind_param("i", $exerciseIdToDelete);
    $stmtSelect->execute();
    $stmtSelect->bind_result($filePath);

    if ($stmtSelect->fetch()) {
      $stmtSelect->close();


      $stmtDelete = $mysqli->prepare("DELETE FROM exercises WHERE exercise_id = ?");
      $stmtDelete->bind_param("i", $exerciseIdToDelete);

      if ($stmtDelete->execute()) {
        unlink($filePath);
        header("Location: exercises.php");
        exit();
      } else {
        echo "Error deleting exercise: " . $stmtDelete->error;
      }

      $stmtDelete->close();

    } else {
      $stmtSelect->close();
      echo "Error retrieving file path from the database.";
    }


  } else {
    echo "Invalid exercise ID to delete.";
  }
}
error:
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_exercise"])) {
  $exerciseName = filter_var($_POST["exercise_name"], FILTER_SANITIZE_STRING);
  $exerciseDescription = filter_var($_POST["exercise_description"], FILTER_SANITIZE_STRING);
  $exerciseMuscle = filter_var($_POST["exercise_muscle"], FILTER_SANITIZE_STRING);

  if (isset($_FILES["exercise_image"]) && $_FILES["exercise_image"]["size"] > 0) {
    $allowedExtensions = array("jpg", "jpeg", "png", "gif", "webm");
    $targetDirectory = "images/";
    $uploadedFile = $targetDirectory . basename($_FILES["exercise_image"]["name"]);
    $uploadOk = true;
    $imageFileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["exercise_image"]["tmp_name"]);
    if ($check === false) {
      $errorMessege = "File is not an image.";
      $uploadOk = false;
    } else if ($_FILES["exercise_image"]["size"] > 500000) {
      $errorMessege = "Sorry, image is too large.";
      $uploadOk = false;
    } else if (!in_array($imageFileType, $allowedExtensions)) {
      $errorMessege = "Only JPG, JPEG, PNG, GIF and WEBM images are allowed.";
      $uploadOk = false;
    }
    if ($uploadOk) {
      if (move_uploaded_file($_FILES["exercise_image"]["tmp_name"], $uploadedFile)) {
        $stmt = $mysqli->prepare("INSERT INTO exercises (name, description, muscle, image_url) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $exerciseName, $exerciseDescription, $exerciseMuscle, $uploadedFile);

        if ($stmt->execute()) {
          header("Location: exercises.php");
          exit();
        } else {
          echo "Error: " . $stmt->error;
        }
        $stmt->close();
      } else {
        $errorMessege = "There was an error uploading the image";
        exit($errorMessege);
      }
    }


  } else {
    $stmt = $mysqli->prepare("INSERT INTO exercises (name, description, muscle) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $exerciseName, $exerciseDescription, $exerciseMuscle);

    if ($stmt->execute()) {
      header("Location: exercises.php");
      exit();
    } else {
      echo "Error: " . $stmt->error;
    }
    $stmt->close();
  }
}

$searchByName = isset($_POST['search_by_name']) ? $_POST['search_by_name'] : '';
$searchByMuscle = isset($_POST['search_by_muscle']) ? $_POST['search_by_muscle'] : '';
$searchByMuscleSearch = '%' . $searchByMuscle . '%';
$searchByNameSearch = '%' . $searchByName . '%';

$query = "SELECT exercise_id, name, description, muscle, image_url FROM exercises 
          WHERE (name LIKE ? OR ? = '')
          AND (muscle LIKE ? OR ? = '')";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ssss", $searchByNameSearch, $searchByNameSearch, $searchByMuscleSearch, $searchByMuscleSearch);

$stmt->execute();

$stmt->bind_result($exerciseId, $exerciseName, $exerciseDescription, $exerciseMuscle, $imageUrl);

require_once 'header.php';
?>
<div class="error-messege">
  <?php echo !empty($errorMessege) ? $errorMessege : ''; ?>
</div>

<h2>Add New Exercise</h2>
<form action="" method="post" class="add-exercise-form" enctype="multipart/form-data">
  <label for="exercise_name">Exercise Name:</label>
  <input type="text" id="exercise_name" name="exercise_name" required>

  <label for="exercise_description">Exercise Description:</label>
  <textarea id="exercise_description" name="exercise_description" rows="3" required></textarea>

  <label for="exercise_muscle">Muscle used:</label>
  <input type="text" id="exercise_muscle" name="exercise_muscle" required>

  <label for="exercise_image">Upload Image: (Optional)</label>
  <input type="file" id="exercise_image" name="exercise_image">

  <button type="submit" name="add_exercise">Add Exercise</button>
  <br>
  <br>
  <br>
</form>


<form action="" method="post" class="search-form">
  <label for="search_by_name">Search by Name:</label>
  <input type="text" id="search_by_name" name="search_by_name" value="<?php echo htmlspecialchars($searchByName); ?>">

  <label for="search_by_muscle">Search by Muscle:</label>
  <input type="text" id="search_by_muscle" name="search_by_muscle"
    value="<?php echo htmlspecialchars($searchByMuscle); ?>">

  <button type="submit" name="search">Search</button>
</form>


<h2>List of Exercises</h2>

<ul class="exercises-list">
  <?php while ($stmt->fetch()): ?>
    <li>
      <strong>
        <?php echo htmlspecialchars($exerciseName); ?>
      </strong><br>
      <?php echo htmlspecialchars($exerciseDescription); ?><br>
      <em>
        <?php echo htmlspecialchars($exerciseMuscle); ?>
      </em><br>

      <?php
      if (!empty($imageUrl) && file_exists($imageUrl)) {
        echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="Exercise Image" style="max-width: 300px; max-height: 300px;">';
      } else {
        echo 'No image available';
      }
      ?>

      <form action="" method="post" class="delete-form">
        <input type="hidden" name="exercise_id" value="<?php echo $exerciseId; ?>">
        <button type="submit" name="delete_exercise">Delete</button>
      </form>
    </li>
  <?php endwhile; ?>
</ul>


<?php require_once 'footer.html';

$stmt->close();
$mysqli->close();
?>