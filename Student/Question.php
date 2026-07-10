<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/CSProject/Teacher/Database.php';

// ------------------------------------------------------------------
// Identify the logged-in student
// ------------------------------------------------------------------
$studentID = (int) ($_SESSION['student_id'] ?? ($_GET['student'] ?? 0));

if (!$studentID) {
    header("Location: StudentLogin.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $studentID);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) {
    session_destroy();
    header("Location: StudentLogin.php");
    exit();
}

// ------------------------------------------------------------------
// Build the personal QR code URL for this student's current tile.
// The QR encodes a link to Answer.php with the student's unique token.
// The question is ONLY revealed after scanning — not shown here.
// ------------------------------------------------------------------
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/');

$token       = $student['qr_token'] ?? '';
$answerUrl   = $baseUrl . '/Answer.php?token=' . urlencode($token);
$qrUrl       = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($answerUrl);

$progressPercent = (int) round(($student['current_tile'] / (BOARD_SIZE - 1)) * 100);
$isWinnerAlready = $student['winner'] === 'Yes';
$lapsCompleted   = (int) ($student['laps_completed'] ?? 0);
$hasTile         = (int) $student['current_tile'] !== 0 && $token !== '';

// Auto-refresh while waiting for teacher to move the student
$shouldAutoRefresh = !$isWinnerAlready && !$hasTile;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NeuroNet Quest — Your Turn</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
<?php if ($shouldAutoRefresh): ?>
<meta http-equiv="refresh" content="5">
<?php endif; ?>
</head>
<body class="question-page">

<div class="player-card card">

    <!-- Player bar -->
    <div class="player-bar">
        <div class="player-name"><?php echo htmlspecialchars($student['student_name']); ?></div>
        <div class="chip-row">
            <span class="chip">Layer <strong><?php echo (int) $student['layer_number']; ?></strong></span>
            <span class="chip">Score <strong><?php echo (int) $student['score']; ?> / <?php echo WINNING_SCORE; ?></strong></span>
            <?php if ($lapsCompleted > 0): ?>
                <span class="chip">Lap <strong><?php echo $lapsCompleted + 1; ?></strong></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Board progress bar -->
    <div class="board-progress">
        <div class="progress-track">
            <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
        </div>
        <div class="board-progress-labels">
            <span>Tile <?php echo (int) $student['current_tile']; ?> / <?php echo BOARD_SIZE - 1; ?></span>
            <span><?php echo WINNING_SCORE; ?> pts to win</span>
        </div>
    </div>

    <?php if ($isWinnerAlready): ?>

        <div class="center-state">
            <span class="big-icon">🏆</span>
            <h2>You reached <?php echo WINNING_SCORE; ?> points!</h2>
            <p>Nice work. Other students are still playing — check the teacher's board for standings.</p>
        </div>

    <?php elseif ($hasTile): ?>

        <!-- QR code reveal -->
        <div class="qr-reveal">
            <p class="qr-reveal-label">Your question is ready on</p>
            <div class="qr-tile-badge">Tile <?php echo (int) $student['current_tile']; ?></div>
            <p class="qr-reveal-sub">Scan your personal QR code below to reveal it</p>
            <div class="qr-reveal-code">
                <img src="<?php echo htmlspecialchars($qrUrl); ?>" alt="Personal QR code for <?php echo htmlspecialchars($student['student_name']); ?>">
            </div>
            <p class="qr-reveal-hint">
                📱 Point your phone camera at the code above<br>
                The question will open on your device
            </p>
        </div>

    <?php else: ?>

        <div class="center-state">
            <span class="big-icon">⏳</span>
            <h2>Waiting for your move</h2>
            <p>
                Your teacher will roll the dice for you.
                <br>Your personal QR code will appear here when it's your turn.
                <span class="pulse-dot"></span><span class="pulse-dot"></span><span class="pulse-dot"></span>
            </p>
        </div>

    <?php endif; ?>

</div>

<script src="script.js"></script>
</body>
</html>