<?php
require_once '../db.php';
require_once '../admin_only.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];

    $stmt = $conn->prepare("DELETE FROM airlines WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->close();

    header("Location: airlines.php");
    exit;
}

$result = $conn->query("SELECT * FROM airlines ORDER BY name ASC");
$airlines = [];

while ($row = $result->fetch_assoc()) {
    $airlines[] = $row;
}
?>