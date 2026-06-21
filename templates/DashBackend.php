<?php
require_once "config.php"; // db connection and config constants

$allClasses = ['25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR'];
$classWeights = array_fill_keys($allClasses, 0);

// Fetches total weight per grade class from DB and returns it as JSON for the dashboard chart.
$sql = "SELECT Classes, SUM(weight) AS total_weight FROM Finger_classes WHERE weight >= 0 GROUP BY Classes";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $class = $row['Classes'];
        if (array_key_exists($class, $classWeights)) {
            // Convert from grams to kilograms and round to 2 decimal places
            $classWeights[$class] = round((float) $row['total_weight'] / 1000, 2);
        }
    }
}

// Total batch count
$totalBatches = 0;
$countResult = $conn->query("SELECT COUNT(*) AS total FROM Finger_classes WHERE weight >= 0");
if ($countResult) {
    $totalBatches = (int) $countResult->fetch_assoc()['total'];
}

// Most recent batch timestamp
$latestTs = null;
$tsResult = $conn->query("SELECT MAX(timestamp) AS latest FROM Finger_classes WHERE weight >= 0");
if ($tsResult) {
    $row = $tsResult->fetch_assoc();
    $latestTs = $row['latest'] ?? null;
}

header('Content-Type: application/json');
echo json_encode([
    'labels'        => array_keys($classWeights),
    'weights'       => array_values($classWeights),
    'total_batches' => $totalBatches,
    'latest_update' => $latestTs,
]);
