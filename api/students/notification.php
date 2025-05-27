<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
$conn = new mysqli("localhost", "root", "", "management");

$result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => (int)$row['notification_id'],
        'student_id' => $row['student_id'],
        'title' => $row['title'],
        'content' => $row['message'], 
        'type' => $row['notification_type'],
        'related_id' => $row['related_id'],
        'read' => (bool)$row['is_read'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode($notifications);
$conn->close();
