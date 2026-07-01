<?php
include 'Database.php';

// ------------------------------------------------------------------
// Actions
// ------------------------------------------------------------------

// Start a brand new game session (keeps existing students/scores)
if (isset($_POST['new_game'])) {
    $code = 'TT-' . random_int(1000, 9999);
    $stmt = mysqli_prepare($conn, "INSERT INTO game_session (game_code, game_status) VALUES (?, 'Running')");
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);

    header("Location: Teacher.php");
    exit();
}

// Reset everything: clear students/answers and start a fresh session
if (isset($_POST['reset_game'])) {
    mysqli_query($conn, "DELETE FROM answers");
    mysqli_query($conn, "DELETE FROM students");
    mysqli_query($conn, "ALTER TABLE students AUTO_INCREMENT = 1");
    mysqli_query($conn, "ALTER TABLE answers AUTO_INCREMENT = 1");

    $code = 'TT-' . random_int(1000, 9999);
    $stmt = mysqli_prepare($conn, "INSERT INTO game_session (game_code, game_status) VALUES (?, 'Running')");
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);

    header("Location: Teacher.php");
    exit();
}

// Move a student by a dice roll
if (isset($_POST['move'])) {
    $studentID = (int) $_POST['student_id'];
    $dice      = (int) $_POST['dice'];

    if ($studentID > 0 && $dice >= 1 && $dice <= 6) {

        $stmt = mysqli_prepare($conn, "SELECT current_tile, laps_completed FROM students WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $studentID);
        mysqli_stmt_execute($stmt);
        $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($student) {
            $rawTile = $student['current_tile'] + $dice;
            $newTile = $rawTile % BOARD_SIZE;
            $newLaps = $student['laps_completed'] + intdiv($rawTile, BOARD_SIZE);

            // Generate a fresh single-use token for this tile visit.
            // The student's QR code will encode a link containing this token.
            $newToken = bin2hex(random_bytes(16));

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE students SET current_tile = ?, laps_completed = ?, qr_token = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "iisi", $newTile, $newLaps, $newToken, $studentID);
            mysqli_stmt_execute($stmt);
        }
    }

    header("Location: Teacher.php");
    exit();
}

// ------------------------------------------------------------------
// Data for the page
// ------------------------------------------------------------------

$gameSession = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM game_session ORDER BY id DESC LIMIT 1")
);

$leaderboardResult = mysqli_query(
    $conn,
    "SELECT * FROM students ORDER BY score DESC, current_tile DESC, student_name ASC"
);
$leaderboard = mysqli_fetch_all($leaderboardResult, MYSQLI_ASSOC);
$totalStudents = count($leaderboard);

// Winner name (if the game has ended)
$winnerName = null;
if ($gameSession && $gameSession['game_status'] === 'Finished' && $gameSession['winner_student']) {
    foreach ($leaderboard as $row) {
        if ((int) $row['id'] === (int) $gameSession['winner_student']) {
            $winnerName = $row['student_name'];
            break;
        }
    }
}

// Status pill class
$statusClass = 'status-draft';
$statusLabel = 'No Game Yet';
if ($gameSession) {
    if ($gameSession['game_status'] === 'Running') {
        $statusClass = 'status-running';
        $statusLabel = 'Running';
    } elseif ($gameSession['game_status'] === 'Finished') {
        $statusClass = 'status-finished';
        $statusLabel = 'Finished';
    } else {
        $statusLabel = htmlspecialchars($gameSession['game_status']);
    }
}

// Build the join link + QR code for students
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/');
$joinUrl  = $baseUrl . '/StudentLogin.php';
$qrUrl    = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($joinUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NeuroNet Quest — Teacher Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body class="teacher-page">

<div class="header">
    <div class="logo">
        <div class="logo-box">T</div>
        <span>TripleT Edu</span>
    </div>
    <div class="nav-btns">
        <a href="student-dashboard.php" class="btn outline">Exit</a>
    </div>
</div>

<div class="dashboard">

    <!-- Top bar -->
    <div class="topbar">
        <div>
            <p class="eyebrow">Hybrid Learning · Board Game</p>
            <h1>NeuroNet Quest</h1>
        </div>

        <div class="topbar-meta">
            <span class="pill">
                Game Code
                <strong><?php echo $gameSession ? htmlspecialchars($gameSession['game_code']) : '—'; ?></strong>
            </span>
            <span class="pill <?php echo $statusClass; ?>">
                <span class="status-dot"></span>
                <?php echo $statusLabel; ?>
            </span>
        </div>
    </div>

    <?php if ($winnerName): ?>
        <div class="winner-banner">
            🏆 <strong><?php echo htmlspecialchars($winnerName); ?></strong> reached <?php echo WINNING_SCORE; ?> points first! Other students can keep playing to see who finishes next.
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="card stat-card">
            <div class="stat-label">Students Joined</div>
            <div class="stat-value"><?php echo $totalStudents; ?></div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Target Score</div>
            <div class="stat-value"><?php echo WINNING_SCORE; ?> <span>pts</span></div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Points / Correct</div>
            <div class="stat-value">+<?php echo POINTS_PER_CORRECT; ?> <span>pts</span></div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Board Size</div>
            <div class="stat-value"><?php echo BOARD_SIZE; ?> <span>tiles · loops</span></div>
        </div>
    </div>

    <!-- Game board -->
    <div class="panel" style="margin-bottom: 24px;">
        <h2 class="panel-title"><span class="dot"></span>Game Board</h2>
        <div id="boardTrack">
            <?php include 'board_track.php'; ?>
        </div>
    </div>

    <!-- Main grid -->
    <div class="main-grid">

        <!-- Left column -->
        <div class="stack">

            <!-- Game controls -->
            <div class="panel">
                <h2 class="panel-title"><span class="dot"></span>Game Controls</h2>
                <form method="POST" style="margin-bottom: 12px;">
                    <button type="submit" name="new_game" class="btn-primary">
                        <?php echo $gameSession ? 'Start New Round' : 'Start Game'; ?>
                    </button>
                </form>
                <form method="POST" onsubmit="return confirm('This will erase all students and scores. Continue?');">
                    <button type="submit" name="reset_game" class="btn-danger">Reset Everything</button>
                </form>
            </div>

            <!-- Join code / QR -->
            <div class="panel">
                <h2 class="panel-title"><span class="dot"></span>Join the Game</h2>
                <div class="qr-card">
                    <img src="<?php echo htmlspecialchars($qrUrl); ?>" alt="QR code to join the game">
                    <div style="flex:1; min-width:0;">
                        <p style="font-size:13px; color:var(--text-muted); margin-bottom:6px;">
                            Students scan this code or open the link below:
                        </p>
                        <div class="join-link" id="joinLink"><?php echo htmlspecialchars($joinUrl); ?></div>
                        <button type="button" class="btn-secondary" id="copyLinkBtn" data-link="<?php echo htmlspecialchars($joinUrl); ?>">
                            Copy Link
                        </button>
                    </div>
                </div>
            </div>

            <!-- Move a student -->
            <div class="panel">
                <h2 class="panel-title"><span class="dot"></span>Move a Student</h2>
                <form method="POST">
                    <label for="student_id">Student</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($leaderboard as $row): ?>
                            <option value="<?php echo (int) $row['id']; ?>">
                                <?php echo htmlspecialchars($row['student_name']); ?>
                                (Tile <?php echo (int) $row['current_tile']; ?><?php echo $row['laps_completed'] > 0 ? ', Lap ' . ((int) $row['laps_completed'] + 1) : ''; ?> — <?php echo (int) $row['score']; ?> pts)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="dice">Dice Roll (1–6)</label>
                    <input type="number" id="dice" name="dice" min="1" max="6" required>

                    <button type="submit" name="move" class="btn-primary">Move Student</button>
                </form>
            </div>

        </div>

        <!-- Right column: leaderboard -->
        <div class="panel">
            <h2 class="panel-title"><span class="dot"></span>Live Leaderboard</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Layer</th>
                            <th>Progress</th>
                            <th>Laps</th>
                            <th>Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="leaderboardBody">
                        <?php include 'leaderboard_rows.php'; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="script.js"></script>
</body>
</html>
