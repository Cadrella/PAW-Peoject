<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $data = json_decode(file_get_contents("php://input"), true);
  $session_id = $data['session_id'] ?? 0;
  
  $stmt = $mysqli->prepare("UPDATE attendance_sessions SET status = 'open' WHERE id = ?");
  $stmt->bind_param("i", $session_id);
  
  if ($stmt->execute()) {
    echo json_encode(['success' => true]);
  }
  $stmt->close();
} catch (Exception $e) {
  echo json_encode(['success' => false]);
}
?>
