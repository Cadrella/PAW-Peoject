<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $data = json_decode(file_get_contents('php://input'), true);
  $code = trim($data['code'] ?? '');
  $name = trim($data['name'] ?? '');

  if (empty($code) || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Course code and name are required']);
    exit;
  }

  $professor_id = $_SESSION['user_id'];

  // Ensure unique code
  $stmt = $mysqli->prepare('SELECT id FROM courses WHERE code = ?');
  $stmt->bind_param('s', $code);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Course code already exists']);
    exit;
  }
  $stmt->close();

  $stmt = $mysqli->prepare('INSERT INTO courses (code, name, professor_id) VALUES (?, ?, ?)');
  $stmt->bind_param('ssi', $code, $name, $professor_id);
  if ($stmt->execute()) {
    $course_id = $stmt->insert_id;
    echo json_encode(['success' => true, 'course_id' => $course_id]);
  } else {
    error_log('Add course error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to add course']);
  }
  $stmt->close();
} catch (Exception $e) {
  error_log('Add course exception: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}

?>
