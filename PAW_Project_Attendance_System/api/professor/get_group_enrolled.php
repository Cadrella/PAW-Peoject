<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $group_id = intval($_GET['group_id'] ?? 0);
  if ($group_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid group']); exit; }

  // Verify professor owns the course for this group
  $stmt = $mysqli->prepare('SELECT c.professor_id FROM groups g JOIN courses c ON g.course_id = c.id WHERE g.id = ?');
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Group not found']); exit; }
  $row = $res->fetch_assoc();
  if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
  $stmt->close();

  $stmt = $mysqli->prepare('SELECT u.id, u.full_name, u.email FROM enrollments e JOIN users u ON e.student_id = u.id WHERE e.group_id = ?');
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $students = [];
  while ($s = $result->fetch_assoc()) { $students[] = $s; }
  echo json_encode(['success'=>true,'data'=>$students]);
  $stmt->close();
} catch (Exception $e) {
  error_log('Get group enrolled error: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
