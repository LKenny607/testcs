<?php
$studentName = "Alya";
$studentInitial = strtoupper(substr($studentName, 0, 1));
$accessLevel = "Layer 1 access";
$score = 0;
$scoreMax = 50;
$progressPercent = ($scoreMax > 0) ? round(($score / $scoreMax) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - NeuroNet Quest</title>
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <video class="bg-video" autoplay muted loop playsinline>
        <source src="video/12421439_3840_2160_30fps.mp4" type="video/mp4">
    </video>
    <div class="bg-video-overlay"></div>

    <?php include 'header.php'; ?>

    <div class="dashboard-wrap">
        <div class="card">
            <div class="profile">
                <div class="avatar"><?php echo $studentInitial; ?></div>
                <div>
                    <h3><?php echo htmlspecialchars($studentName); ?></h3>
                    <p><?php echo htmlspecialchars($accessLevel); ?></p>
                </div>
            </div>

            <p>Score progress</p>
            <div class="progress-bar" style="--progress: <?php echo $progressPercent; ?>%;"></div>
            <p><?php echo $score; ?> / <?php echo $scoreMax; ?></p>

            <div class="badges">
                <span>Tile 0</span>
                <span>Streak 0</span>
                <span>Reward ready: No</span>
            </div>
        </div>
    </div>

</body>
</html>
