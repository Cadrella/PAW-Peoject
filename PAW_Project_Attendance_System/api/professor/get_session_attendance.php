<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $session_id = intval($_GET['session_id'] ?? 0);
  if ($session_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

  // verify session exists and professor owns the related course/group
  $stmt = $mysqli->prepare('SELECT s.id, s.course_id, c.professor_id FROM attendance_sessions s JOIN courses c ON s.course_id = c.id WHERE s.id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) { $stmt->close(); echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }
    $row = $res->fetch_assoc();
    if ($row['professor_id'] != $_SESSION['user_id']) { $stmt->close(); echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
    $stmt->close();
  } else {
    $dberr = $mysqli->error;
    error_log('get_session_attendance.php - failed to prepare session ownership query: ' . $dberr);
    // Return DB error for local debugging (safe for local dev); remove in production
    echo json_encode(['success'=>false,'message'=>'Server error', 'db_error' => $dberr]);
    exit;
  }
  // First try to read a JSON blob from `attendance_records.data` (preferred aggregated storage)
  try {
    $stmt = $mysqli->prepare('SELECT data FROM attendance_records WHERE session_id = ? LIMIT 1');
    if ($stmt) {
      $stmt->bind_param('i', $session_id);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $json = $row['data'];
        $decoded = json_decode($json, true);
        $map = [];
        if (is_array($decoded)) {
          foreach ($decoded as $rec) {
            $sid = intval($rec['student_id'] ?? 0);
            if ($sid <= 0) continue;
            $present = !empty($rec['present']) ? 'present' : 'absent';
            $map[$sid] = $present;
          }
        }
        echo json_encode(['success'=>true,'data'=>$map]);
        $stmt->close();
        exit;
      }
      $stmt->close();
    }
  } catch (Exception $e) {
    error_log('Blob attendance lookup failed (attendance_records): ' . $e->getMessage());
  }

  // Try blob from legacy-named table `atten_records` (in case DB still uses old name)
  try {
    $stmt = $mysqli->prepare('SELECT data FROM atten_records WHERE session_id = ? LIMIT 1');
    if ($stmt) {
      $stmt->bind_param('i', $session_id);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $json = $row['data'];
        $decoded = json_decode($json, true);
        $map = [];
        if (is_array($decoded)) {
          foreach ($decoded as $rec) {
            $sid = intval($rec['student_id'] ?? 0);
            if ($sid <= 0) continue;
            $present = !empty($rec['present']) ? 'present' : 'absent';
            $map[$sid] = $present;
          }
        }
        echo json_encode(['success'=>true,'data'=>$map]);
        $stmt->close();
        exit;
      }
      $stmt->close();
    }
  } catch (Exception $e) {
    error_log('Blob attendance lookup failed (atten_records): ' . $e->getMessage());
  }

  // Fallback: legacy per-row attendance table. Try `attendance_records` first, then `atten_records`.
  $data = [];
  // Prefer integer `presence` column if available; otherwise fall back to textual `status`.
  $stmt = $mysqli->prepare('SELECT student_id, presence FROM attendance_records WHERE session_id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $session_id);
    if ($stmt->execute()) {
      $result = $stmt->get_result();
      while ($r = $result->fetch_assoc()) { $data[$r['student_id']] = (!empty($r['presence']) ? 'present' : 'absent'); }
      $stmt->close();
      echo json_encode(['success'=>true,'data'=>$data]);
      exit;
    }
    $stmt->close();
  }

  // If presence column not available, try legacy `status` textual column
  $stmt = $mysqli->prepare('SELECT student_id, status FROM attendance_records WHERE session_id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $session_id);
    if ($stmt->execute()) {
      $result = $stmt->get_result();
      while ($r = $result->fetch_assoc()) { $data[$r['student_id']] = $r['status']; }
      $stmt->close();
      echo json_encode(['success'=>true,'data'=>$data]);
      exit;
    }
    $stmt->close();
  }

  // If attendance_records per-row not available, try atten_records per-row
  $stmt = $mysqli->prepare('SELECT student_id, status FROM atten_records WHERE session_id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $session_id);
    if ($stmt->execute()) {
      $result = $stmt->get_result();
      while ($r = $result->fetch_assoc()) { $data[$r['student_id']] = $r['status']; }
      $stmt->close();
      echo json_encode(['success'=>true,'data'=>$data]);
      exit;
    }
    $stmt->close();
  }

  error_log('No attendance_records or atten_records table found or failed to query: ' . $mysqli->error);
  echo json_encode(['success'=>true,'data'=>new stdClass()]);
} catch (Exception $e) {
  error_log('Get session attendance error: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
