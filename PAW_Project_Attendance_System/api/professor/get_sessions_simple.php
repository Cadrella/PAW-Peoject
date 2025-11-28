<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $group_id = intval($_GET['group_id'] ?? 0);
  if ($group_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid group']); exit; }

  // Verify group and get course
  $stmt = $mysqli->prepare('SELECT g.course_id, c.professor_id FROM groups g JOIN courses c ON g.course_id = c.id WHERE g.id = ?');
  if (!$stmt) { error_log('Prepare failed (get_sessions_simple group): ' . $mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Group not found']); exit; }
  $row = $res->fetch_assoc();
  if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
  $course_id = intval($row['course_id']);
  $stmt->close();

  // Try to select session_time, fallback to `time` if that column doesn't exist
  $stmt = $mysqli->prepare("SELECT s.id, s.name as session_name, s.session_date, s.session_time, s.created_at FROM sessions s WHERE s.course_id = ? AND s.group_id = ? ORDER BY s.session_date DESC, s.session_time DESC");
  if (!$stmt && $mysqli->errno === 1054) {
    $stmt = $mysqli->prepare("SELECT s.id, s.name as session_name, s.session_date, s.`time` AS session_time, s.created_at FROM sessions s WHERE s.course_id = ? AND s.group_id = ? ORDER BY s.session_date DESC, s.`time` DESC");
  }
  if (!$stmt) { error_log('Prepare failed (get_sessions_simple select): ' . $mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
  $stmt->bind_param('ii', $course_id, $group_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $sessions = [];
  while ($s = $result->fetch_assoc()) { $sessions[] = $s; }

  echo json_encode(['success'=>true,'data'=>$sessions,'course_id'=>$course_id]);
  $stmt->close();
} catch (Exception $e) {
  error_log('Get sessions simple error: ' . $e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
