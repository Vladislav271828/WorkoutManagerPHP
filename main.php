<?php

require_once 'auth.php';

checkAuthentication();
$pageTitle = "Main Page";

require_once 'header.php';
?>
<h2>Welcome to the Main Page</h2>
<p>💪💪💪💪💪💪💪💪💪💪💪💪💪💪</p>


<div>
  <a href="trainers.php" class="button">Trainers</a>
  <a href="exercises.php" class="button">Exercises</a>
  <a href="workouts.php" class="button">Workouts</a>
</div>

<a href="logout.php" class="button logout">Logout</a>
</body>

</html>

</html>

<?php
require_once 'footer.php';
?>