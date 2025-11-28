<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['admin']);

try {
  $result = $mysqli->query("SELECT id, email, full_name FROM users WHERE role = 'student'");
  if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
  }

  $students = [];
  while ($row = $result->fetch_assoc()) {
    $students[] = $row;
  }

  echo json_encode(['success' => true, 'data' => $students]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error']);
}
?>
