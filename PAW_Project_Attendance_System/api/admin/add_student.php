<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['admin']);

try {
  $data = json_decode(file_get_contents("php://input"), true);
  
  $email = $data['email'] ?? '';
  $full_name = $data['full_name'] ?? '';
  $password = password_hash($data['password'] ?? '', PASSWORD_BCRYPT);
  
  $stmt = $mysqli->prepare("INSERT INTO users (email, full_name, password, role) VALUES (?, ?, ?, 'student')");
  $stmt->bind_param("sss", $email, $full_name, $password);
  
  if ($stmt->execute()) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false]);
  }
  $stmt->close();
} catch (Exception $e) {
  echo json_encode(['success' => false]);
}
?>
