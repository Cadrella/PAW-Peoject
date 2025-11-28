<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $session_id = intval($_GET['session_id'] ?? 0);
  if ($session_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

  // verify session exists and professor owns the related course (best-effort)
  $stmt = $mysqli->prepare('SELECT s.id, s.course_id, c.professor_id FROM attendance_sessions s JOIN courses c ON s.course_id = c.id WHERE s.id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) { $stmt->close(); echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }
    $row = $res->fetch_assoc();
    if ($row['professor_id'] != $_SESSION['user_id']) { $stmt->close(); echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
    $stmt->close();
  }

  // Query attendance_records joined with users to return student info and presence/participation ints
  $out = [];
  $stmt = $mysqli->prepare('SELECT ar.student_id, u.full_name, COALESCE(ar.presence, 0) AS presence, COALESCE(ar.participation, 0) AS participation FROM attendance_records ar JOIN users u ON ar.student_id = u.id WHERE ar.session_id = ? ORDER BY u.full_name');
  if ($stmt) {
    $stmt->bind_param('i', $session_id);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      while ($r = $res->fetch_assoc()) {
        $out[] = [
          'student_id' => (int)$r['student_id'],
          'full_name' => $r['full_name'],
          'presence' => (int)$r['presence'],
          'participation' => (int)$r['participation']
        ];
      }
    }
    $stmt->close();
    echo json_encode(['success'=>true,'data'=>$out]);
    exit;
  }

  // If prepare failed or table not present, return empty
  echo json_encode(['success'=>true,'data'=>[]]);

} catch (Exception $e) {
  error_log('get_session_records error: ' . $e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
