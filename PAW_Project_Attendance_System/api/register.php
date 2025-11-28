<?php
header('Content-Type: application/json');
require_once '../config/db.php';

try {
  $data = json_decode(file_get_contents("php://input"), true);
  
  $email = trim($data['email'] ?? '');
  $password = password_hash($data['password'] ?? '', PASSWORD_BCRYPT);
  $full_name = trim($data['full_name'] ?? '');
  $role = $data['role'] ?? 'student';
  
  $stmt = $mysqli->prepare("INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("ssss", $email, $password, $full_name, $role);
  
  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'User registered']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
  }
  $stmt->close();
} catch (Exception $e) {
  error_log("Register error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
