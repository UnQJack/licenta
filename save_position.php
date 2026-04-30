<?php
require_once 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$flight_id = (int)($data['flight_id'] ?? 0);
$lat = (float)($data['lat'] ?? 0);
$lon = (float)($data['lon'] ?? 0);
$altitude = (int)($data['altitude'] ?? 0);
$speed = (int)($data['speed'] ?? 0);
$heading = (int)($data['heading'] ?? 90);
$vertical_speed = isset($data['vertical_speed']) ? (int)$data['vertical_speed'] : null;

if ($flight_id <= 0) {
    echo json_encode(["status" => "error", "message" => "flight_id invalid"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO positions (
        flight_id,
        recorded_at,
        lat,
        lon,
        altitude,
        speed,
        heading,
        vertical_speed
    )
    VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iddiiii",
    $flight_id,
    $lat,
    $lon,
    $altitude,
    $speed,
    $heading,
    $vertical_speed
);

$stmt->execute();
$stmt->close();

echo json_encode(["status" => "ok"]);