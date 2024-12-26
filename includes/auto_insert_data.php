<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host     = 'localhost';
$user     = 'root';
$password = 'root';
$database = 'election_monitoring';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Input: Number of records for each table
$records = [
  'districts'        => 1000,
  'polling_stations' => 1000,
  'candidates'       => 5,
  'ballots'          => 50,
  'votes'            => 500
];

// Generate districts
for ($i = 1; $i <= $records['districts']; $i++) {
  $name         = "District " . $i;
  $total_voters = rand(1000, 5000);
  $query        = "INSERT INTO districts (name, total_voters) VALUES ('$name', $total_voters)";
  if (!$conn->query($query)) {
    die("Error inserting into districts: " . $conn->error);
  }
}

// Generate polling stations
for ($i = 1; $i <= $records['polling_stations']; $i++) {
  $district_id  = rand(1, $records['districts']);
  $name         = "Station " . $i;
  $total_voters = rand(500, 1000);
  $query        = "INSERT INTO polling_stations (name, district_id, total_voters) VALUES ('$name', $district_id, $total_voters)";
  if (!$conn->query($query)) {
    die("Error inserting into polling_stations: " . $conn->error);
  }
}

// Generate candidates
for ($i = 1; $i <= $records['candidates']; $i++) {
  $name        = "Candidate " . $i;
  $party       = "Party " . chr(65 + ($i % 26)); // A-Z parties
  $full_name   = $name . " (" . $party . ")";
  $district_id = rand(1, $records['districts']);
  $query       = "INSERT INTO candidates (name, party, full_name, district_id) 
              VALUES ('$name', '$party', '$full_name', $district_id)";
  if (!$conn->query($query)) {
    die("Error inserting into candidates: " . $conn->error);
  }
}

// Generate ballots
for ($i = 1; $i <= $records['ballots']; $i++) {
  $station_id       = rand(1, $records['polling_stations']);
  $ballots_received = rand(500, 1000);
  $ballots_unused   = rand(0, 50);
  $ballots_spoiled  = rand(0, 20);
  $ballots_issued   = $ballots_received - $ballots_unused - $ballots_spoiled;
  $query            = "INSERT INTO ballots (station_id, ballots_received, ballots_unused, ballots_spoiled, ballots_issued) 
              VALUES ($station_id, $ballots_received, $ballots_unused, $ballots_spoiled, $ballots_issued)";
  if (!$conn->query($query)) {
    die("Error inserting into ballots: " . $conn->error);
  }
}

// Get the range of candidate IDs
$result = $conn->query("SELECT MIN(candidate_id) AS min_id, MAX(candidate_id) AS max_id FROM candidates");
$row = $result->fetch_assoc();
$min_candidate_id = $row['min_id'];
$max_candidate_id = $row['max_id'];

// Generate votes
for ($i = 1; $i <= $records['votes']; $i++) {
  $station_id = rand(1, $records['polling_stations']);
  $candidate_id = rand($min_candidate_id, $max_candidate_id); // Ensure candidate_id exists
  $votes_received = rand(1, 50);
  $query = "INSERT INTO votes (station_id, candidate_id, votes_received) 
              VALUES ($station_id, $candidate_id, $votes_received)";
  if (!$conn->query($query)) {
    die("Error inserting into votes: " . $conn->error);
  }
}

echo "Data inserted successfully.";

header("Location: /");
exit();

$conn->close();
?>
