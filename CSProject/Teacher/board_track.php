<?php
// This file is included by Teacher.php AND fetched directly via AJAX
// (see script.js) to refresh the board without reloading the page.
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

// Tile labels — tile 0 = Start (corner), tile 8 = Final Gate (corner),
// the rest run along the two long edges. BOARD_SIZE = 16 tiles total (0-15),
// after which a student's current_tile wraps back to 1 (see Teacher.php).
$tileLabels = [
    0  => 'Start',
    1  => 'AI Basics',
    2  => 'Neurons',
    3  => 'Input Layer',
    4  => 'Weights',
    5  => 'Activation',
    6  => 'ReLU',
    7  => 'Forward Pass',
    8  => 'Final Gate',
    9  => 'Loss',
    10 => 'Gradient',
    11 => 'Backprop',
    12 => 'Epoch',
    13 => 'Overfitting',
    14 => 'Learning Rate',
    15 => 'CNN',
];

// Group students by the tile they're currently standing on (already wrapped
// 0-15 by Teacher.php), and rank them for token color/number.
$tileOccupants = array_fill(0, BOARD_SIZE, []);
$rank = 1;
foreach ($leaderboard as $row) {
    $tile = (int) $row['current_tile'];
    if ($tile < 0) $tile = 0;
    if ($tile >= BOARD_SIZE) $tile = $tile % BOARD_SIZE;

    $tileOccupants[$tile][] = [
        'name'   => $row['student_name'],
        'rank'   => $rank,
        'winner' => $row['winner'] === 'Yes',
        'laps'   => (int) ($row['laps_completed'] ?? 0),
    ];
    $rank++;
}

$tokenColors = ['#5EE6FF', '#FF6B81', '#3DDC97', '#FFC658', '#8B7CFF', '#FF9F6B', '#6BCBFF', '#E0AE7E'];

// Build the perimeter order: a 16-tile board sits as a 5x5 square frame
// (4 corners + 4 tiles per edge), arranged clockwise starting at Start
// (bottom-left corner), matching classic Monopoly direction.
//
//   [12] [11] [10] [9]  [8]
//   [13]                [7]
//   [14]                [6]
//   [15]                [5]
//   [0]  [1]  [2]  [3]  [4]
//
$gridMap = [
    [12, 11, 10, 9, 8],
    [13, null, null, null, 7],
    [14, null, null, null, 6],
    [15, null, null, null, 5],
    [0, 1, 2, 3, 4],
];

function renderTile(int $t, array $tileOccupants, array $tileLabels, array $tokenColors): string {
    $occupants = $tileOccupants[$t] ?? [];
    $isStart   = ($t === 0);
    $isFinal   = ($t === 8);
    $tileClass = 'board-tile' . ($isStart ? ' tile-start' : '') . ($isFinal ? ' tile-final' : '');

    $html  = '<div class="' . $tileClass . '">';
    $html .= '<div class="tile-number">' . $t . '</div>';
    $html .= '<div class="tile-label">' . htmlspecialchars($tileLabels[$t] ?? ('Tile ' . $t)) . '</div>';

    if (!empty($occupants)) {
        $html .= '<div class="tile-tokens">';
        foreach ($occupants as $p) {
            $color  = $tokenColors[($p['rank'] - 1) % count($tokenColors)];
            $lapTag = $p['laps'] > 0 ? ' · Lap ' . ($p['laps'] + 1) : '';
            $title  = htmlspecialchars($p['name'] . ' — Rank #' . $p['rank'] . $lapTag);
            $cls    = 'token' . ($p['winner'] ? ' token-winner' : '');
            $label  = $p['winner'] ? '★' : (string) $p['rank'];
            $html  .= '<div class="' . $cls . '" style="background:' . $color . ';" title="' . $title . '">' . $label . '</div>';
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}
?>
<div class="board-frame">
    <?php foreach ($gridMap as $row): ?>
        <div class="board-row">
            <?php foreach ($row as $tileNum): ?>
                <?php if ($tileNum === null): ?>
                    <div class="board-cell board-empty"></div>
                <?php else: ?>
                    <div class="board-cell">
                        <?php echo renderTile($tileNum, $tileOccupants, $tileLabels, $tokenColors); ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="board-center">
        <div class="board-center-title">NeuroNet<br>Quest</div>
        <div class="board-center-sub">First to <?php echo WINNING_SCORE; ?> pts wins</div>
    </div>
</div>

<?php if (!empty($leaderboard)): ?>
<div class="board-legend">
    <?php $rank = 1; foreach ($leaderboard as $row):
        $color = $tokenColors[($rank - 1) % count($tokenColors)];
        $laps  = (int) ($row['laps_completed'] ?? 0);
    ?>
        <div class="legend-item">
            <span class="legend-dot" style="background: <?php echo $color; ?>;"><?php echo $rank; ?></span>
            <span><?php echo htmlspecialchars($row['student_name']); ?><?php echo $laps > 0 ? ' (Lap ' . ($laps + 1) . ')' : ''; ?></span>
        </div>
    <?php $rank++; endforeach; ?>
</div>
<?php endif; ?>
