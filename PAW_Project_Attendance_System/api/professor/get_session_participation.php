<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $session_id = intval($_GET['session_id'] ?? 0);
  if ($session_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid session']); exit; }

  // verify session ownership quickly (best-effort)
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

  // Try blob JSON first (attendance_records.data)
  try {
    $stmt = $mysqli->prepare('SELECT data FROM attendance_records WHERE session_id = ? LIMIT 1');
    if ($stmt) {
      $stmt->bind_param('i', $session_id);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $decoded = json_decode($row['data'], true);
        $map = [];
        if (is_array($decoded)) {
          foreach ($decoded as $rec) {
            $sid = intval($rec['student_id'] ?? 0);
            if ($sid <= 0) continue;
            $map[$sid] = !empty($rec['participation']) ? 1 : 0;
          }
        }
        echo json_encode(['success'=>true,'data'=>$map]);
        $stmt->close();
        exit;
      }
      $stmt->close();
    }
  } catch (Exception $e) {
    error_log('Blob participation lookup failed (attendance_records): ' . $e->getMessage());
  }

  // Try blob from legacy-named table `atten_records` as a fallback
  try {
    $stmt = $mysqli->prepare('SELECT data FROM atten_records WHERE session_id = ? LIMIT 1');
    if ($stmt) {
      $stmt->bind_param('i', $session_id);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $decoded = json_decode($row['data'], true);
        $map = [];
        if (is_array($decoded)) {
          foreach ($decoded as $rec) {
            $sid = intval($rec['student_id'] ?? 0);
            if ($sid <= 0) continue;
            $map[$sid] = !empty($rec['participation']) ? 1 : 0;
          }
        }
        echo json_encode(['success'=>true,'data'=>$map]);
        $stmt->close();
        exit;
      }
      $stmt->close();
    }
  } catch (Exception $e) {
    error_log('Blob participation lookup failed (atten_records): ' . $e->getMessage());
  }

  // Fallback: try per-row `participation` integer column in attendance_records
  try {
    $stmt = $mysqli->prepare('SELECT student_id, participation FROM attendance_records WHERE session_id = ?');
    if ($stmt) {
      $stmt->bind_param('i', $session_id);
      $stmt->execute();
      $res = $stmt->get_result();
      $map = [];
      while ($r = $res->fetch_assoc()) { $map[$r['student_id']] = (int)$r['participation']; }
      $stmt->close();
      echo json_encode(['success'=>true,'data'=>$map]);
      exit;
    }
  } catch (Exception $e) {
    error_log('Per-row participation lookup failed: ' . $e->getMessage());
  }

  // Fallback: no participation data available, return empty map
  echo json_encode(['success'=>true,'data'=>new stdClass()]);

} catch (Exception $e) {
  error_log('get_session_participation error: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
