<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $q = trim($_GET['q'] ?? '');
  if ($q === '') {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
  }

  $like = "%" . $q . "%";
  $stmt = $mysqli->prepare("SELECT id, full_name, email FROM users WHERE role = 'student' AND full_name LIKE ? LIMIT 50");
  $stmt->bind_param('s', $like);
  $stmt->execute();
  $result = $stmt->get_result();

  $data = [];
  while ($row = $result->fetch_assoc()) {
    $data[] = $row;
  }

  echo json_encode(['success' => true, 'data' => $data]);
  $stmt->close();
} catch (Exception $e) {
  error_log('Search students error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}

?>
