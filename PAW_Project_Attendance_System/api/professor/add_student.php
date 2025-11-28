<?php
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
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Group not found']); exit; }
  $row = $res->fetch_assoc();
  if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized for this group']); exit; }
  $course_id = intval($row['course_id']);
  $stmt->close();

  // Check existing enrollment
  $stmt = $mysqli->prepare('SELECT id FROM enrollments WHERE student_id = ? AND group_id = ?');
  $stmt->bind_param('ii', $student_id, $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows > 0) { echo json_encode(['success'=>false,'message'=>'Student already enrolled in this group']); exit; }
  $stmt->close();

  // Insert enrollment
  $stmt = $mysqli->prepare('INSERT INTO enrollments (student_id, group_id) VALUES (?, ?)');
  $stmt->bind_param('ii', $student_id, $group_id);
  if ($stmt->execute()) {
    echo json_encode(['success' => true]);
  } else {
    error_log('Enroll student error: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to enroll student']);
  }
  $stmt->close();
} catch (Exception $e) {
  error_log('Add student exception: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}

?>
