<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $data = json_decode(file_get_contents("php://input"), true);

  $session_id = intval($data['session_id'] ?? 0);
  $records = $data['records'] ?? null;

  // Allow single-record payload as fallback
  if (!is_array($records)) {
    if (isset($data['student_id'])) {
      $records = [[
        'student_id' => intval($data['student_id']),
        'present' => $data['present'] ?? 0,
        'participation' => $data['participation'] ?? 0
      ]];
    }
  }

  if ($session_id <= 0 || !is_array($records)) { echo json_encode(['success' => false, 'message' => 'Invalid input']); exit; }

  // Best-effort ownership check: if `sessions` table exists and is joinable to courses, require professor owns it
  $stmtCheck = $mysqli->prepare('SELECT s.id, c.professor_id FROM sessions s JOIN courses c ON s.course_id = c.id WHERE s.id = ?');
  if ($stmtCheck) {
    $stmtCheck->bind_param('i', $session_id);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    if ($res && $res->num_rows > 0) {
      $r = $res->fetch_assoc();
      if ($r['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success' => false, 'message' => 'Not authorized']); exit; }
    }
    $stmtCheck->close();
  }

  // Insert/update per-row in attendance_records using integer columns `presence` and `participation` (0/1).
  // This expects the table `attendance_records` to have columns: session_id, student_id, presence, participation
  $sql = "INSERT INTO attendance_records (session_id, student_id, presence, participation) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE presence = VALUES(presence), participation = VALUES(participation)";
  $stmt = $mysqli->prepare($sql);
  if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Prepare failed', 'error' => $mysqli->error]); exit; }

  $mysqli->begin_transaction();
  $rows = 0;
  $errors = [];
  foreach ($records as $rec) {
    $sid = intval($rec['student_id'] ?? 0);
    if ($sid <= 0) continue;
    $presence = isset($rec['presence']) ? (int)$rec['presence'] : (isset($rec['present']) ? (int)$rec['present'] : 0);
    $participation = isset($rec['participation']) ? (int)$rec['participation'] : 0;
    $stmt->bind_param('iiii', $session_id, $sid, $presence, $participation);
    if (!$stmt->execute()) {
      $errors[] = ['student_id' => $sid, 'error' => $stmt->error];
      break;
    }
    $rows += ($stmt->affected_rows >= 0) ? $stmt->affected_rows : 0;
  }

  if (!empty($errors)) {
    $mysqli->rollback();
    // Log detailed errors to PHP error log for server-side debugging
    error_log('mark_attendance.php - failed rows: ' . json_encode($errors));
    echo json_encode(['success' => false, 'message' => 'Failed to save some rows', 'errors' => $errors]);
  } else {
    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Attendance saved', 'rows' => $rows]);
  }

  $stmt->close();

} catch (Exception $e) {
  if ($mysqli->errno) $mysqli->rollback();
  echo json_encode(['success' => false, 'message' => 'Exception', 'error' => $e->getMessage()]);
}

?>
