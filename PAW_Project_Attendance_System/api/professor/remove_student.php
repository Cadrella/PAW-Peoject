Tr<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $data = json_decode(file_get_contents('php://input'), true);
  $student_id = intval($data['student_id'] ?? 0);
  $group_id = intval($data['group_id'] ?? 0);

  if ($student_id <= 0 || $group_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
  }

  // Verify group exists and professor owns the related course
  $stmt = $mysqli->prepare('SELECT g.course_id, c.professor_id FROM groups g JOIN courses c ON g.course_id = c.id WHERE g.id = ?');
  if (!$stmt) { error_log('Prepare failed (remove_student group check): ' . $mysqli->error); echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Group not found']); exit; }
  $row = $res->fetch_assoc();
  if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized for this group']); exit; }
  $stmt->close();

  // Check enrollment exists
  $stmt = $mysqli->prepare('SELECT id FROM enrollments WHERE student_id = ? AND group_id = ?');
  if (!$stmt) { error_log('Prepare failed (remove_student select): ' . $mysqli->error); echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
  $stmt->bind_param('ii', $student_id, $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Enrollment not found']); exit; }
  $stmt->close();

  // Delete enrollment
  $stmt = $mysqli->prepare('DELETE FROM enrollments WHERE student_id = ? AND group_id = ?');
  if (!$stmt) { error_log('Prepare failed (remove_student delete): ' . $mysqli->error); echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
  $stmt->bind_param('ii', $student_id, $group_id);
  if ($stmt->execute()) {
    echo json_encode(['success' => true]);
  } else {
    error_log('Remove student error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to remove student']);
  }
  $stmt->close();
} catch (Exception $e) {
  error_log('Remove student exception: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}

?>
