<?php
// In production, these would come from session/database.
// Hardcoded for now so the page works standalone.
$studentName = "Alya";
$studentInitial = strtoupper(substr($studentName, 0, 1));
$accessLevel = "Layer 1 access";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - NeuroNet Quest</title>
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <video class="bg-video" autoplay muted loop playsinline>
        <source src="video/background.mp4" type="video/mp4">
    </video>
    <div class="bg-video-overlay"></div>

    <?php include 'header.php'; ?>

    <div class="dashboard-wrap">

        <div class="dashboard-hero">
            <div class="avatar"><?php echo $studentInitial; ?></div>
            <div>
                <h2><?php echo htmlspecialchars($studentName); ?></h2>
                <p><?php echo htmlspecialchars($accessLevel); ?></p>
            </div>
        </div>

        <div class="dashboard-grid">

            <a href="game.php" class="node-card enter-game">
                <div class="node-icon">▶</div>
                <h3>Enter Game</h3>
                <p>Jump back into your board and continue answering questions.</p>
            </a>

            <a href="about.php" class="node-card">
                <div class="node-icon">i</div>
                <h3>About</h3>
                <p>Learn how NeuroNet Quest works and what each tile means.</p>
            </a>

            <a href="profile.php" class="node-card">
                <div class="node-icon"><?php echo $studentInitial; ?></div>
                <h3>View Profile</h3>
                <p>See your name, avatar, score, and badges.</p>
            </a>

            <a href="feedback.php" class="node-card">
                <div class="node-icon">✎</div>
                <h3>Feedback</h3>
                <p>Tell us what you liked or what felt confusing.</p>
            </a>

        </div>

    </div>

</body>
</html>
