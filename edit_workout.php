<?php
require_once 'auth.php';
checkAuthentication();
require_once 'baza.php';



$pageTitle = "Edit Workout";

$workoutId = null;
$userId = null;
$trainerId = null;
$workoutDate = null;
$workoutName = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST["edit_workout"])) {

    $workoutId = filter_var($_POST["workout_id"], FILTER_VALIDATE_INT);
    $trainerId = filter_var($_POST["trainer_id"], FILTER_VALIDATE_INT);
    $datetime = filter_var($_POST["datetime"], FILTER_SANITIZE_STRING);
    $workoutName = filter_var($_POST["workout_name"], FILTER_SANITIZE_STRING);
    $exerciseSetsReps = $_POST["exercise_sets_reps"];

    $stmtUpdateWorkout = $mysqli->prepare("UPDATE workouts SET trainer_id = ?, name = ?, workout_date = ? WHERE workout_id = ?");
    $stmtUpdateWorkout->bind_param("issi", $trainerId, $workoutName, $datetime, $workoutId);

    if ($stmtUpdateWorkout->execute()) {
 
      $stmtDeleteExercises = $mysqli->prepare("DELETE FROM workout_exercises WHERE workout_id = ?");
      $stmtDeleteExercises->bind_param("i", $workoutId);
      $stmtDeleteExercises->execute();
      $stmtDeleteExercises->close();


      foreach ($exerciseSetsReps as $exerciseId => $setsReps) {
        if ($setsReps["sets"] == 0) {
          $stmtInsertExercise = $mysqli->prepare("DELETE FROM workout_exercises WHERE workout_id = ? AND exercise_id = ?");
          $stmtInsertExercise->bind_param("ii", $workoutId, $exerciseId);
          $stmtInsertExercise->execute();
          $stmtInsertExercise->close();
          continue;
        }
        if ($setsReps["sets"] < 0) {
          $setsReps["sets"] = 0;
        }
        if ($setsReps["reps"] < 0) {
          $setsReps["reps"] = 0;
        }
        $stmtInsertExercise = $mysqli->prepare("INSERT INTO workout_exercises (workout_id, exercise_id, sets, reps) VALUES (?, ?, ?, ?)");
        $stmtInsertExercise->bind_param("iiii", $workoutId, $exerciseId, $setsReps["sets"], $setsReps["reps"]);
        $stmtInsertExercise->execute();
        $stmtInsertExercise->close();
      }

      header("Location: workouts.php");
      exit();
    } else {
      echo "Error updating workout: " . $stmtUpdateWorkout->error;
    }

    $stmtUpdateWorkout->close();
  } elseif (isset($_POST["delete_workout"])) {

    $workoutIdToDelete = filter_var($_POST["workout_id"], FILTER_VALIDATE_INT);

    if ($workoutIdToDelete !== false) {

      $stmtDeleteExercises = $mysqli->prepare("DELETE FROM workout_exercises WHERE workout_id = ?");
      $stmtDeleteExercises->bind_param("i", $workoutIdToDelete);

      if ($stmtDeleteExercises->execute()) {
        $stmtDeleteExercises->close();


        $stmtDeleteWorkout = $mysqli->prepare("DELETE FROM workouts WHERE workout_id = ?");
        $stmtDeleteWorkout->bind_param("i", $workoutIdToDelete);

        if ($stmtDeleteWorkout->execute()) {
          header("Location: workouts.php");
          exit();
        } else {
          echo "Error deleting workout: " . $stmtDeleteWorkout->error;
        }

        $stmtDeleteWorkout->close();
      } else {
        echo "Error deleting workout exercises: " . $stmtDeleteExercises->error;
      }
    } else {
      echo "Invalid workout ID to delete.";
    }
  }
}



if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["workout_id"])) {
  $workoutId = filter_var($_GET["workout_id"], FILTER_VALIDATE_INT);

  $stmt = $mysqli->prepare("SELECT user_id, trainer_id, workout_date, name FROM workouts WHERE workout_id = ?");
  $stmt->bind_param("i", $workoutId);
  $stmt->execute();
  $stmt->bind_result($userId, $trainerId, $workoutDate, $workoutName);

  if (!$stmt->fetch()) {
    echo "Workout not found.";
    $stmt->close();
    exit();
  }

  $stmt->close();
}

require_once 'header.php';
?>

<?php
$stmtTrainers = $mysqli->prepare("SELECT trainer_id, name FROM trainers");
$stmtTrainers->execute();
$resultTrainers = $stmtTrainers->get_result();
?>

<?php
$stmtExercises = $mysqli->prepare("SELECT we.exercise_id, e.name, e.description, e.muscle, e.image_url, we.sets, we.reps FROM workout_exercises we JOIN exercises e ON we.exercise_id = e.exercise_id WHERE we.workout_id = ?");
$stmtExercises->bind_param("i", $workoutId);
$stmtExercises->execute();
$resultExercises = $stmtExercises->get_result();
?>

<form action="edit_workout.php" method="post" class="edit-workout-form">
  <input type="hidden" name="workout_id" value="<?php echo $workoutId; ?>">

  <label for="workout_name">Name of Workout:</label>
  <input type="text" id="workout_name" name="workout_name" value="<?php echo htmlspecialchars($workoutName); ?>"
    required>

  <label for="trainer_id">Select Trainer:</label>
  <select id="trainer_id" name="trainer_id" required>
    <?php while ($trainer = $resultTrainers->fetch_assoc()): ?>
      <?php $selected = ($trainer['trainer_id'] == $trainerId) ? 'selected' : ''; ?>
      <option value="<?php echo $trainer['trainer_id']; ?>" <?php echo $selected; ?>>
        <?php echo htmlspecialchars($trainer['name']); ?>
      </option>
    <?php endwhile; ?>
  </select>

  <label for="datetime">Date and Time:</label>
  <input type="datetime-local" id="datetime" name="datetime"
    value="<?php echo date('Y-m-d\TH:i', strtotime($workoutDate)); ?>" required>

  <label>Exercises, Sets, and Reps:</label>
  <?php while ($exercise = $resultExercises->fetch_assoc()): ?>
    <div class="exercise-entry">
      <label for="sets_reps_<?php echo $exercise['exercise_id']; ?>">

      </label>
      <br>
      <br>
      <strong>
        <?php echo htmlspecialchars($exercise['name']); ?>
      </strong><br>
      <?php echo htmlspecialchars($exercise['description']); ?><br>
      <em>
        <?php echo htmlspecialchars($exercise['muscle']); ?>
      </em><br>

      <?php if (!empty($exercise['image_url']) && file_exists($exercise['image_url'])): ?>
        <img src="<?php echo htmlspecialchars($exercise['image_url']); ?>" alt="Exercise Image"
          style="max-width: 300px; max-height: 300px;">
      <?php else: ?>
        No image available
      <?php endif; ?>
      <br>
      <input type="number" id="sets_reps_<?php echo $exercise['exercise_id']; ?>"
        name="exercise_sets_reps[<?php echo $exercise['exercise_id']; ?>][sets]" value="<?php echo $exercise['sets']; ?>"
        placeholder="Sets" required min="0">
      <input type="number" name="exercise_sets_reps[<?php echo $exercise['exercise_id']; ?>][reps]"
        value="<?php echo $exercise['reps']; ?>" placeholder="Reps" required min="0">
    </div>
  <?php endwhile; ?>
  <br>
  <button type="submit" name="edit_workout" class="button">Update Workout</button>
</form>
<form action="edit_workout.php" method="post" class="delete-workout-form">
  <input type="hidden" name="workout_id" value="<?php echo $workoutId; ?>">
  <button type="submit" class="button logout" name="delete_workout">Delete Workout</button>
</form>

<?php require_once 'footer.html'; ?>