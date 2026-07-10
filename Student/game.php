<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NeuroNet Quest - My Board</title>
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <video class="bg-video" autoplay muted loop playsinline>
        <source src="video/background.mp4" type="video/mp4">
    </video>
    <div class="bg-video-overlay"></div>

    <?php include 'header.php'; ?>

    <div class="container">

        <!-- LEFT SIDE -->
        <div>
            <!-- PROFILE -->
            <div class="card">
                <div class="profile">
                    <div class="avatar">A</div>
                    <div>
                        <h3>Alya</h3>
                        <p>Layer 1 access</p>
                    </div>
                </div>

                <p>Score progress</p>
                <div class="progress-bar" style="--progress: 0%;"></div>
                <p>0 / 50</p>

                <div class="badges">
                    <span>Tile 0</span>
                    <span>Streak 0</span>
                    <span>Reward ready: No</span>
                </div>
            </div>

        </div>

        <!-- RIGHT SIDE -->
        <div>
            <!-- QUESTION -->
            <div class="card question-box">
                <div class="question-header">
                    <h3>Question</h3>
                    <button class="scan" onclick="openScanner()">Scan My QR</button>
                </div>
                <p>Answer correctly to earn points and build a streak.</p>

                <div class="alert">
                    Scan your QR code after the teacher enters your dice number.
                </div>
            </div>

            <!-- BOARD -->
            <div class="card">
                <h3>My Board</h3>
                <p>Student sees their current Monopoly position.</p>

                <div class="board">
                    <?php
                    $tiles = [
                        "Start","Math","Science","Chance","English","History",
                        "Bonus","Geography","Tax","Coding","Art","Jail Visit",
                        "Physics","Community","Biology","Quiz Duel","Chemistry","Library",
                        "Music","Final Gate"
                    ];

                    $currentTile = 0;

                    foreach ($tiles as $index => $tile) {
                        $currentClass = ($index === $currentTile) ? ' current' : '';
                        echo "<div class='tile{$currentClass}'>{$index}<br>{$tile}</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

    </div>

    <!-- QR SCANNER MODAL -->
    <div class="qr-modal-overlay" id="qr-modal-overlay">
        <div class="qr-modal">
            <div class="qr-modal-header">
                <h3>Scan My QR</h3>
                <button class="qr-close-btn" onclick="closeScanner()" aria-label="Close scanner">✕</button>
            </div>

            <div id="qr-reader"></div>

            <p class="qr-modal-hint">Point your camera at your personal QR code.</p>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrCode;

        function openScanner() {
            document.getElementById("qr-modal-overlay").classList.add("active");

            html5QrCode = new Html5Qrcode("qr-reader");

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    html5QrCode.start(
                        devices[0].id,
                        {
                            fps: 10,
                            qrbox: 220
                        },
                        onScanSuccess
                    ).catch(err => {
                        console.error("Unable to start camera:", err);
                    });
                }
            }).catch(err => {
                console.error("Unable to list cameras:", err);
            });
        }

        function closeScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    document.getElementById("qr-modal-overlay").classList.remove("active");
                }).catch(() => {
                    // camera may already be stopped
                    document.getElementById("qr-modal-overlay").classList.remove("active");
                });
            } else {
                document.getElementById("qr-modal-overlay").classList.remove("active");
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            alert("QR Scanned: " + decodedText);
            closeScanner();
        }

        // allow closing by clicking outside the modal box
        document.getElementById("qr-modal-overlay").addEventListener("click", function (e) {
            if (e.target === this) {
                closeScanner();
            }
        });
    </script>

</body>
</html>
