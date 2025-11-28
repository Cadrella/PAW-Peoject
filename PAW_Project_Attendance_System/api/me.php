<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/auth.php';

// auth.php calls session_start()
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Not authenticated']);
  exit;
}

echo json_encode([
  'success' => true,
  'user_id' => $_SESSION['user_id'] ?? null,
  'role' => $_SESSION['role'] ?? null,
  'full_name' => $_SESSION['full_name'] ?? null
]);

?>
