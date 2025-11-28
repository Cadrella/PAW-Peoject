<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['admin']);

try {
  $data = json_decode(file_get_contents("php://input"), true);
  $student_id = $data['student_id'] ?? 0;
  
  $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
  $stmt->bind_param("i", $student_id);
  
  if ($stmt->execute()) {
    echo json_encode(['success' => true]);
  }
  $stmt->close();
} catch (Exception $e) {
  echo json_encode(['success' => false]);
}
?>
