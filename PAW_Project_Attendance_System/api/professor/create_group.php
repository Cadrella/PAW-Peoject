<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $data = json_decode(file_get_contents('php://input'), true);
  $course_id = intval($data['course_id'] ?? 0);
  $group_name = trim($data['group_name'] ?? '');

  if ($course_id <= 0 || $group_name === '') { echo json_encode(['success'=>false,'message'=>'Invalid input']); exit; }

  // Verify professor owns the course
  $stmt = $mysqli->prepare('SELECT professor_id FROM courses WHERE id = ?');
  $stmt->bind_param('i', $course_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Course not found']); exit; }
  $row = $res->fetch_assoc();
  if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
  $stmt->close();

  // Check duplicate name
  $stmt = $mysqli->prepare('SELECT id FROM groups WHERE course_id = ? AND group_name = ?');
  $stmt->bind_param('is', $course_id, $group_name);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows > 0) { echo json_encode(['success'=>false,'message'=>'Group name already exists']); exit; }
  $stmt->close();

  $stmt = $mysqli->prepare('INSERT INTO groups (course_id, group_name) VALUES (?, ?)');
  $stmt->bind_param('is', $course_id, $group_name);
  if ($stmt->execute()) {
    echo json_encode(['success'=>true,'group_id'=>$stmt->insert_id]);
  } else {
    error_log('Create group error: '.$stmt->error);
    echo json_encode(['success'=>false,'message'=>'Failed to create group']);
  }
  $stmt->close();
} catch (Exception $e) {
  error_log('Create group exception: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
