<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['admin']);

try {
  $data = json_decode(file_get_contents('php://input'), true);
  $email = trim($data['email'] ?? '');
  $password = $data['password'] ?? '';
  $full_name = trim($data['full_name'] ?? '');

  if (empty($email) || empty($password) || empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'Email, password and full name are required']);
    exit;
  }

  // Check if email exists
  $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
  }
  $stmt->close();

  $hash = password_hash($password, PASSWORD_BCRYPT);
  $role = 'admin';

  $stmt = $mysqli->prepare('INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, ?)');
  $stmt->bind_param('ssss', $email, $hash, $full_name, $role);
  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Admin registered']);
  } else {
    error_log('Register admin error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
  }
  $stmt->close();
} catch (Exception $e) {
  error_log('Register admin exception: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}

?>
