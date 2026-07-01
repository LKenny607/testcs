<?php
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In production: save $_POST['feedback'] to a database.
    $submitted = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback - NeuroNet Quest</title>
  <link rel="stylesheet" href="CSS/style.css">
  <style>
    textarea {
        width: 100%;
        min-height: 120px;
        background: var(--bg-light);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-sm);
        color: var(--text-main);
        padding: 12px;
        font-family: inherit;
        font-size: 14px;
        resize: vertical;
    }
    .submit-btn {
        margin-top: 12px;
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        background: linear-gradient(135deg, var(--accent), var(--primary));
        color: #04111f;
        font-weight: 600;
        cursor: pointer;
    }
  </style>
</head>
<body>

    <video class="bg-video" autoplay muted loop playsinline>
        <source src="video/12421439_3840_2160_30fps.mp4" type="video/mp4">
    </video>
    <div class="bg-video-overlay"></div>

    <?php include 'header.php'; ?>

    <div class="dashboard-wrap">
        <div class="card">
            <h3>Feedback</h3>
            <p>Tell us what you liked or what felt confusing about NeuroNet Quest.</p>

            <?php if ($submitted): ?>
                <div class="alert">Thanks — your feedback has been recorded.</div>
            <?php endif; ?>

            <form method="POST">
                <textarea name="feedback" placeholder="Type your feedback here..." required></textarea>
                <br>
                <button type="submit" class="submit-btn">Send Feedback</button>
            </form>
        </div>
    </div>

</body>
</html>
