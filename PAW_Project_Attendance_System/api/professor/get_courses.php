<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $professor_id = $_SESSION['user_id'];
  
  $stmt = $mysqli->prepare("SELECT id, code, name FROM courses WHERE professor_id = ?");
  $stmt->bind_param("i", $professor_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $courses = [];
  while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
  }
  
  echo json_encode(['success' => true, 'data' => $courses]);
  $stmt->close();
} catch (Exception $e) {
  error_log("Get courses error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
