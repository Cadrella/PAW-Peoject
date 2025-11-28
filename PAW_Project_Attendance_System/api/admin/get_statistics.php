<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['admin']);

try {
  // Total students, professors, courses
  $result = $mysqli->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
  if ($result === false) { echo json_encode(['success'=>false,'message'=>'Database error']); exit; }
  $total_students = $result->fetch_assoc()['total'];

  $result = $mysqli->query("SELECT COUNT(*) as total FROM users WHERE role = 'professor'");
  if ($result === false) { echo json_encode(['success'=>false,'message'=>'Database error']); exit; }
  $total_professors = $result->fetch_assoc()['total'];

  $result = $mysqli->query("SELECT COUNT(*) as total FROM courses");
  if ($result === false) { echo json_encode(['success'=>false,'message'=>'Database error']); exit; }
  $total_courses = $result->fetch_assoc()['total'];

  $result = $mysqli->query("SELECT COUNT(*) as total FROM attendance_records WHERE status = 'present'");
  if ($result === false) { echo json_encode(['success'=>false,'message'=>'Database error']); exit; }
  $total_present = $result->fetch_assoc()['total'];
  
  echo json_encode([
    'success' => true,
    'data' => [
      'total_students' => $total_students,
      'total_professors' => $total_professors,
      'total_courses' => $total_courses,
      'total_present' => $total_present
    ]
  ]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error']);
}
?>
