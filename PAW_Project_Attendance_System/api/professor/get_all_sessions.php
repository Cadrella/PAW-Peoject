<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor','admin']);

try {
  // Optional filter by course_id (for convenience)
  $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

  // Base select attempts to use `session_time`; fallback to `time` column if it doesn't exist
  $sql = "SELECT s.id, s.name AS session_name, s.session_date, s.session_time, s.course_id, s.group_id, s.created_at, c.name AS course_name, g.group_name FROM sessions s LEFT JOIN courses c ON s.course_id = c.id LEFT JOIN `groups` g ON s.group_id = g.id";
  if ($course_id > 0) {
    $sql .= " WHERE s.course_id = ?";
  }
  $sql .= " ORDER BY s.session_date DESC, s.session_time DESC";

  $stmt = $mysqli->prepare($sql);
  if (!$stmt && $mysqli->errno === 1054) {
    // column doesn't exist, try fallback using `time`
    $sql = "SELECT s.id, s.name AS session_name, s.session_date, s.`time` AS session_time, s.course_id, s.group_id, s.created_at, c.name AS course_name, g.group_name FROM sessions s LEFT JOIN courses c ON s.course_id = c.id LEFT JOIN `groups` g ON s.group_id = g.id";
    if ($course_id > 0) {
      $sql .= " WHERE s.course_id = ?";
    }
    $sql .= " ORDER BY s.session_date DESC, s.`time` DESC";
    $stmt = $mysqli->prepare($sql);
  }

  if (!$stmt) {
    error_log('Prepare failed (get_all_sessions): ' . $mysqli->error);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
  }

  if ($course_id > 0) {
    $stmt->bind_param('i', $course_id);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  $sessions = [];
  while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
  }

  echo json_encode(['success' => true, 'data' => $sessions]);
  $stmt->close();
} catch (Exception $e) {
  error_log('get_all_sessions error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}

?>
