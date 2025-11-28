<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['student']);

try {
  $data = json_decode(file_get_contents("php://input"), true);
  
  $attendance_id = $data['attendance_id'] ?? 0;
  $reason = $data['reason'] ?? '';
  
  $stmt = $mysqli->prepare("INSERT INTO justifications (attendance_id, reason, status) VALUES (?, ?, 'pending')");
  $stmt->bind_param("is", $attendance_id, $reason);
  
  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Justification submitted']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed']);
  }
  $stmt->close();
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error']);
}
?>
