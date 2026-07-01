<?php
// ------------------------------------------------------------------
// Database connection
// ------------------------------------------------------------------
$host     = "localhost";
$user     = "root";
$password = "";
$database = "csproject"; // matches the schema you provided (USE csproject;)

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// ------------------------------------------------------------------
// Game settings (shared across all pages)
// ------------------------------------------------------------------
define("WINNING_SCORE", 50);     // First student to reach this score wins
define("BOARD_SIZE", 16);        // Tiles 0-15 on the board; tile 16 wraps back to 1
define("POINTS_PER_CORRECT", 5); // Points awarded for a correct answer
?>
