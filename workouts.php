<?php

require_once 'auth.php';
checkAuthentication();

require_once 'baza.php';

$pageTitle = "Workouts";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_workout"])) {
    $datetime = filter_var($_POST["datetime"], FILTER_SANITIZE_STRING);
    $exerciseSetsReps = $_POST["exercise_sets_reps"];
    $WorkoutName = filter_var($_POST["workout_name"], FILTER_SANITIZE_STRING);

    if (isset($_POST["trainer_id"]) && !empty($_POST["trainer_id"])) {
        $trainerId = filter_var($_POST["trainer_id"], FILTER_VALIDATE_INT);
    } else {
        $trainerId = NULL;
    }

    $stmtWorkout = $mysqli->prepare("INSERT INTO workouts (user_id, trainer_id, workout_date, name) VALUES (?, ?, ?, ?)");
    $stmtWorkout->bind_param("isss", $_SESSION["user_id"], $trainerId, $datetime, $WorkoutName);


    if ($stmtWorkout->execute()) {
        $workoutId = $mysqli->insert_id;

        foreach ($exerciseSetsReps as $exerciseId => $setsReps) {
            if ($setsReps["sets"] == 0 && $setsReps["reps"] == 0) {
                continue;
            }
            if ($setsReps["sets"] < 0) {
                $setsReps["sets"] = 0;            
            }
            if ($setsReps["reps"] < 0) {
                $setsReps["reps"] = 0;            
            }
            $stmtWorkoutExercises = $mysqli->prepare("INSERT INTO workout_exercises (workout_id, exercise_id, sets, reps) VALUES (?, ?, ?, ?)");
            $stmtWorkoutExercises->bind_param("iiii", $workoutId, $exerciseId, $setsReps["sets"], $setsReps["reps"]);
            $stmtWorkoutExercises->execute();
            $stmtWorkoutExercises->close();
        }

        header("Location: workouts.php");
        exit();
    } else {
        echo "Error adding workout: " . $stmtWorkout->error;
    }

    $stmtWorkout->close();
}


$stmtTrainers = $mysqli->prepare("SELECT trainer_id, name FROM trainers");
$stmtTrainers->execute();
$resultTrainers = $stmtTrainers->get_result();

$stmtExercises = $mysqli->prepare("SELECT exercise_id, name FROM exercises");
$stmtExercises->execute();
$resultExercises = $stmtExercises->get_result();

$query = "SELECT workout_id, trainer_id, workout_date, name FROM workouts WHERE user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$stmt->bind_result($workoutId, $trainerId, $workoutDate, $WorkoutName);

require_once 'header.php';
?>

<form action="" method="post" class="add-workout-form">

    <label for="workout_name">Name of Workout:</label>
    <input type="text" id="workout_name" name="workout_name" required>

    <label for="trainer_id">Select Trainer:</label>
    <select id="trainer_id" name="trainer_id">
        <option value="">No Trainer</option>
        <?php while ($trainer = $resultTrainers->fetch_assoc()): ?>
            <option value="<?php echo $trainer['trainer_id']; ?>">
                <?php echo htmlspecialchars($trainer['name']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="datetime">Date and Time:</label>
    <input type="datetime-local" id="datetime" name="datetime" required>

    <label>Exercises, Sets, and Reps:</label>
    <?php while ($exercise = $resultExercises->fetch_assoc()): ?>
        <div class="exercise-entry">
            <label for="sets_reps_<?php echo $exercise['exercise_id']; ?>">
                <?php echo htmlspecialchars($exercise['name']); ?>:
            </label>
            <input type="number" id="sets_reps_<?php echo $exercise['exercise_id']; ?>"
                name="exercise_sets_reps[<?php echo $exercise['exercise_id']; ?>][sets]" placeholder="Sets" required
                value="0" min="0">

            <input type="number" name="exercise_sets_reps[<?php echo $exercise['exercise_id']; ?>][reps]" placeholder="Reps"
                required value="0" min="0">

        </div>
    <?php endwhile; ?>

    <button type="submit" name="add_workout">Add Workout</button>
</form>

<h2>List of Workouts</h2>

<ul class="workouts-list">
    <?php while ($stmt->fetch()): ?>
        <li>
            <a href="edit_workout.php?workout_id=<?php echo $workoutId; ?>">
                <?php echo "Workout " . htmlspecialchars($WorkoutName) . " on " . htmlspecialchars($workoutDate); ?>
            </a>
        </li>
    <?php endwhile; ?>
</ul>


<?php
require_once 'footer.html';
$stmtTrainers->close();
$stmtExercises->close();

?>