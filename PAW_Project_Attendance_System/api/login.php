<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/auth.php';

try {
  $data = json_decode(file_get_contents("php://input"), true);
  
  $email = $data['email'] ?? '';
  $password = $data['password'] ?? '';

  if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
  }

  if (login($email, $password)) {
    echo json_encode(['success' => true, 'role' => $_SESSION['role']]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
  }
} catch (Exception $e) {
  error_log("Login API error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Login error']);
}
?>
