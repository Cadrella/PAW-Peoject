<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['student']);

try {
  $student_id = $_SESSION['user_id'];
  $course_id = intval($_GET['course_id'] ?? 0);

  // If a course_id is provided, return sessions for that course with the student's attendance (including missing records)
  if ($course_id > 0) {
    $stmt = $mysqli->prepare(
      "SELECT s.id AS session_id, s.session_date, s.session_time, ar.id AS attendance_id, ar.status, 
              COALESCE(ar.presence, (CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END)) AS presence, 
              COALESCE(ar.participation, 0) AS participation
       FROM attendance_sessions s 
       LEFT JOIN attendance_records ar ON s.id = ar.session_id AND ar.student_id = ? 
       WHERE s.course_id = ? 
       ORDER BY s.session_date DESC"
    );
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance = [];
    while ($row = $result->fetch_assoc()) { $attendance[] = $row; }
    echo json_encode(['success' => true, 'data' => $attendance]);
    $stmt->close();
    exit;
  }

  // Otherwise, return all attendance_records rows for this student across all sessions
  $stmt = $mysqli->prepare(
    "SELECT ar.id AS attendance_id, ar.session_id, s.session_date, s.session_time, s.course_id, COALESCE(c.name, '') AS course_name,
            COALESCE(ar.presence, (CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END)) AS presence,
            COALESCE(ar.participation, 0) AS participation, ar.status, ar.recorded_at
     FROM attendance_records ar
     JOIN attendance_sessions s ON ar.session_id = s.id
     LEFT JOIN courses c ON s.course_id = c.id
     WHERE ar.student_id = ?
     ORDER BY s.session_date DESC"
  );
  if ($stmt) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance = [];
    while ($row = $result->fetch_assoc()) { $attendance[] = $row; }
    echo json_encode(['success' => true, 'data' => $attendance]);
    $stmt->close();
    exit;
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error']);
}
?>
