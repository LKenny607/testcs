<?php
// This file is included by Teacher.php AND fetched directly via AJAX
// (see script.js) to refresh the leaderboard without reloading the page.
// It must be able to run standalone, so it sets up its own data.

if (!isset($conn)) {
    include 'Database.php';
}

if (!isset($leaderboard)) {
    $leaderboardResult = mysqli_query(
        $conn,
        "SELECT * FROM students ORDER BY score DESC, current_tile DESC, student_name ASC"
    );
    $leaderboard = mysqli_fetch_all($leaderboardResult, MYSQLI_ASSOC);
}

if (empty($leaderboard)) {
    echo '<tr><td colspan="7" style="text-align:center; color: var(--text-muted);">
            No students have joined yet — share the QR code to get started.
          </td></tr>';
    return;
}

$rank = 1;
foreach ($leaderboard as $row) {

    $rankClass = '';
    if ($rank === 1) $rankClass = 'rank-1';
    elseif ($rank === 2) $rankClass = 'rank-2';
    elseif ($rank === 3) $rankClass = 'rank-3';

    $progress = (int) round((((int) $row['current_tile']) / (BOARD_SIZE - 1)) * 100);
    $laps     = (int) ($row['laps_completed'] ?? 0);

    echo '<tr>';
    echo '<td class="rank-cell ' . $rankClass . '">#' . $rank . '</td>';
    echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
    echo '<td>' . (int) $row['layer_number'] . '</td>';
    echo '<td>
            <div class="progress-track">
                <div class="progress-fill" style="width: ' . $progress . '%;"></div>
            </div>
          </td>';
    echo '<td>Lap ' . ($laps + 1) . '</td>';
    echo '<td>' . (int) $row['score'] . ' / ' . WINNING_SCORE . '</td>';
    echo '<td>';

    // The "status" column was created as STATUS (uppercase) in the schema,
    // so check both possible key cases just in case.
    $onlineStatus = $row['STATUS'] ?? $row['status'] ?? 'Offline';

    if ($row['winner'] === 'Yes') {
        echo '<span class="badge badge-winner">🏆 Winner</span>';
    } elseif ($onlineStatus === 'Online') {
        echo '<span class="badge badge-online">● Online</span>';
    } else {
        echo '<span class="badge badge-offline">○ Offline</span>';
    }

    echo '</td>';
    echo '</tr>';

    $rank++;
}
?>
