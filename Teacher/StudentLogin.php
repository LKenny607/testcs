<?php
session_start();
include 'Database.php';

// Look at the most recent game session created by the teacher
$gameSession = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM game_session ORDER BY id DESC LIMIT 1")
);

$errorMessage = "";

// Handle the join form
if (isset($_POST['login'])) {

    $name  = trim($_POST['student_name']);
    $email = trim($_POST['student_email']);

    if ($name === "") {
        $errorMessage = "Please enter your name to join the game.";
    } else {

        // Each student is randomly placed on one of the 4 board layers
        $layer = random_int(1, 4);

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO students (student_name, student_email, layer_number)
             VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssi", $name, $email, $layer);
        mysqli_stmt_execute($stmt);

        // Remember this student for the rest of the session
        $_SESSION['student_id'] = mysqli_insert_id($conn);

        header("Location: Question.php");
        exit();
    }
}

$gameFinished = $gameSession && $gameSession['game_status'] === 'Finished';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join NeuroNet Quest</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

<div class="login-card card">
    <div class="login-glow"></div>

    <p class="eyebrow">NeuroNet Quest</p>
    <h1>Join the Game</h1>
    <p class="subtitle">Race across the board and learn how neural networks think.</p>

    <?php if ($gameSession): ?>
        <div class="game-code-pill">
            Game Code <span><?php echo htmlspecialchars($gameSession['game_code']); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="banner banner-error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($gameFinished): ?>
        <div class="banner banner-info">
            This game has already ended. Ask your teacher to start a new game before joining.
        </div>
    <?php else: ?>
        <form method="POST">
            <label for="student_name">Your name</label>
            <input type="text" id="student_name" name="student_name" placeholder="e.g. Alya" required autofocus>

            <label for="student_email">Email (optional)</label>
            <input type="email" id="student_email" name="student_email" placeholder="e.g. alya@email.com">

            <button type="submit" name="login" class="btn-primary">Join Game</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
