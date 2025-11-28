<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $course_id = intval($_GET['course_id'] ?? 0);
  if ($course_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid course']); exit; }

  // Verify professor owns the course
  $stmt = $mysqli->prepare('SELECT professor_id FROM courses WHERE id = ?');
  $stmt->bind_param('i', $course_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Course not found']); exit; }
  $row = $res->fetch_assoc();
  if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
  $stmt->close();

  $stmt = $mysqli->prepare('SELECT id, group_name, created_at FROM groups WHERE course_id = ? ORDER BY created_at DESC');
  $stmt->bind_param('i', $course_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $groups = [];
  while ($g = $result->fetch_assoc()) { $groups[] = $g; }
  echo json_encode(['success'=>true,'data'=>$groups]);
  $stmt->close();
} catch (Exception $e) {
  error_log('Get groups error: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
