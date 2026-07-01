<?php
session_start();
include 'Database.php';

// ------------------------------------------------------------------
// Validate the token from the QR code URL
// ------------------------------------------------------------------
$token = trim($_GET['token'] ?? '');

if ($token === '') {
    die("Invalid or missing token. Please scan your personal QR code.");
}

// Look up the student by their token
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE qr_token = ?");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) {
    die("This QR code is invalid or has already been used. Wait for your teacher to move you to the next tile.");
}

$studentID = (int) $student['id'];

// ------------------------------------------------------------------
// Pick (or recall) the random question for this tile visit.
// Stored in session so the same question stays stable until answered.
// ------------------------------------------------------------------
function pickQuestionForTile($conn, $layer, $tile) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM questions WHERE layer_number = ? AND tile_number = ? ORDER BY RAND() LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "ii", $layer, $tile);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$sessionKey = 'active_question_' . $studentID;

// Clear session if the student is on tile 0 (Start — no question)
if ((int) $student['current_tile'] === 0) {
    unset($_SESSION[$sessionKey]);
}

// ------------------------------------------------------------------
// Handle answer submission
// ------------------------------------------------------------------
$feedback = null; // "correct" | "incorrect" | null

if (isset($_POST['submit']) && in_array($_POST['answer'] ?? '', ['A', 'B', 'C', 'D'], true)) {

    $answer = $_POST['answer'];

    // Use the question that was shown (from session), not a re-randomized one
    $activeQuestion = null;
    if (isset($_SESSION[$sessionKey]['id'])) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM questions WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION[$sessionKey]['id']);
        mysqli_stmt_execute($stmt);
        $activeQuestion = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }

    if ($activeQuestion) {

        $isCorrect = ($answer === $activeQuestion['correct_answer']) ? 1 : 0;

        // Log this attempt in the answers table
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO answers (student_id, question_id, selected_answer, is_correct)
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iisi", $studentID, $activeQuestion['id'], $answer, $isCorrect);
        mysqli_stmt_execute($stmt);

        if ($isCorrect) {
            $feedback = "correct";
            $newScore = $student['score'] + POINTS_PER_CORRECT;
            $isWinner = ($newScore >= WINNING_SCORE) ? 'Yes' : 'No';

            $stmt = mysqli_prepare($conn, "UPDATE students SET score = ?, winner = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "isi", $newScore, $isWinner, $studentID);
            mysqli_stmt_execute($stmt);

            // Record first winner in game_session (game continues for others)
            if ($isWinner === 'Yes') {
                $existingWinner = mysqli_fetch_assoc(
                    mysqli_query($conn, "SELECT winner_student FROM game_session ORDER BY id DESC LIMIT 1")
                );
                if ($existingWinner && empty($existingWinner['winner_student'])) {
                    $winnerId = (int) $studentID;
                    mysqli_query(
                        $conn,
                        "UPDATE game_session SET winner_student = $winnerId ORDER BY id DESC LIMIT 1"
                    );
                }
            }

        } else {
            // Wrong answer → move back one tile
            $feedback = "incorrect";
            $newTile  = max(0, $student['current_tile'] - 1);

            $stmt = mysqli_prepare($conn, "UPDATE students SET current_tile = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $newTile, $studentID);
            mysqli_stmt_execute($stmt);
        }

        // Clear the session question slot and INVALIDATE the QR token
        // so this QR code cannot be used again for the same tile visit.
        unset($_SESSION[$sessionKey]);
        $stmt = mysqli_prepare($conn, "UPDATE students SET qr_token = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $studentID);
        mysqli_stmt_execute($stmt);

        // Re-fetch the student with latest state
        $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $studentID);
        mysqli_stmt_execute($stmt);
        $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

// ------------------------------------------------------------------
// Load the question for the current tile
// ------------------------------------------------------------------
$question = null;

if ((int) $student['current_tile'] !== 0 && $feedback === null) {

    // Reuse the session-pinned question if still on the same tile
    if (
        isset($_SESSION[$sessionKey]) &&
        $_SESSION[$sessionKey]['tile']  === (int) $student['current_tile'] &&
        $_SESSION[$sessionKey]['layer'] === (int) $student['layer_number']
    ) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM questions WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION[$sessionKey]['id']);
        mysqli_stmt_execute($stmt);
        $question = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }

    // Otherwise pick a fresh random question from the pool
    if (!$question) {
        $question = pickQuestionForTile($conn, $student['layer_number'], $student['current_tile']);
        if ($question) {
            $_SESSION[$sessionKey] = [
                'id'    => $question['id'],
                'tile'  => (int) $student['current_tile'],
                'layer' => (int) $student['layer_number'],
            ];
        }
    }
}

$progressPercent = (int) round(($student['current_tile'] / (BOARD_SIZE - 1)) * 100);
$isWinnerAlready = $student['winner'] === 'Yes';
$lapsCompleted   = (int) ($student['laps_completed'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NeuroNet Quest — Answer</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
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

    <!-- Progress bar -->
    <div class="board-progress">
        <div class="progress-track">
            <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
        </div>
        <div class="board-progress-labels">
            <span>Tile <?php echo (int) $student['current_tile']; ?> / <?php echo BOARD_SIZE - 1; ?></span>
            <span><?php echo WINNING_SCORE; ?> pts to win</span>
        </div>
    </div>

    <!-- Feedback banners -->
    <?php if ($feedback === "correct"): ?>
        <div class="banner banner-success">
            ✅ Correct! +<?php echo POINTS_PER_CORRECT; ?> points. Go back to your Question page for your next turn.
        </div>
    <?php elseif ($feedback === "incorrect"): ?>
        <div class="banner banner-error">
            ❌ Incorrect — you moved back one tile. Go back to your Question page to wait for your next turn.
        </div>
    <?php endif; ?>

    <?php if ($isWinnerAlready && $feedback !== null): ?>
        <div class="center-state">
            <span class="big-icon">🏆</span>
            <h2>You reached <?php echo WINNING_SCORE; ?> points!</h2>
            <p>Amazing — you're the first to reach the target! Other students keep playing.</p>
        </div>

    <?php elseif ($feedback === null && $question): ?>

        <h2 class="quiz-question"><?php echo htmlspecialchars($question['question_text']); ?></h2>

        <form method="POST" id="answerForm">
            <!-- Pass the token so the form posts back to the same URL -->
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="options">
                <?php foreach (['A','B','C','D'] as $letter): ?>
                    <label class="option">
                        <input type="radio" name="answer" value="<?php echo $letter; ?>" required>
                        <span class="opt-letter"><?php echo $letter; ?></span>
                        <span><?php echo htmlspecialchars($question['option_' . strtolower($letter)]); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" name="submit" class="btn-primary">Submit Answer</button>
        </form>

    <?php elseif ($feedback === null): ?>

        <div class="center-state">
            <span class="big-icon">❓</span>
            <h2>No question found</h2>
            <p>There's no question set up for this tile yet. Let your teacher know.</p>
        </div>

    <?php endif; ?>

</div>

<script src="script.js"></script>
</body>
</html>
