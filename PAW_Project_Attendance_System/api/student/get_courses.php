<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['student']);

try {
  $student_id = $_SESSION['user_id'];
  
  $stmt = $mysqli->prepare("
    SELECT DISTINCT c.id, c.code, c.name, u.full_name as professor_name 
    FROM courses c 
    JOIN groups g ON c.id = g.course_id 
    JOIN enrollments e ON g.id = e.group_id 
    JOIN users u ON c.professor_id = u.id 
    WHERE e.student_id = ?
  ");
  $stmt->bind_param("i", $student_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $courses = [];
  while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
  }
  
  echo json_encode(['success' => true, 'data' => $courses]);
  $stmt->close();
} catch (Exception $e) {
  error_log("Get courses error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}
?>
