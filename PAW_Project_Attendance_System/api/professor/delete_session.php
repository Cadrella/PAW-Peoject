<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $data = json_decode(file_get_contents('php://input'), true);
  $session_id = intval($data['session_id'] ?? 0);
  if ($session_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid session id']); exit; }
  // First try to find session in new `sessions` table
  $stmt = $mysqli->prepare('SELECT s.course_id, s.group_id, c.professor_id FROM sessions s JOIN courses c ON s.course_id = c.id WHERE s.id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
      $row = $res->fetch_assoc();
      if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
      $stmt->close();

      // delete the session row
      $stmt = $mysqli->prepare('DELETE FROM sessions WHERE id = ?');
      if (!$stmt) { error_log('Prepare failed (delete sessions): '.$mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
      $stmt->bind_param('i', $session_id);
      if ($stmt->execute()) { echo json_encode(['success'=>true]); exit; }
      else { error_log('Delete sessions failed: '.$stmt->error); echo json_encode(['success'=>false,'message'=>'Failed to delete session']); exit; }
    }
    $stmt->close();
  }

  // Fallback: look for session in legacy attendance_sessions table
  $stmt = $mysqli->prepare('SELECT s.course_id, c.professor_id FROM attendance_sessions s JOIN courses c ON s.course_id = c.id WHERE s.id = ?');
  if (!$stmt) { error_log('Prepare failed (attendance_sessions lookup): '.$mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
  $stmt->bind_param('i', $session_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Session not found']); exit; }
  $row = $res->fetch_assoc();
  if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
  $stmt->close();

  $mysqli->begin_transaction();
  try {
    $stmt = $mysqli->prepare('DELETE FROM attendance_records WHERE session_id = ?');
    $stmt->bind_param('i', $session_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare('DELETE FROM attendance_sessions WHERE id = ?');
    $stmt->bind_param('i', $session_id);
    if ($stmt->execute()) {
      $stmt->close();
      $mysqli->commit();
      echo json_encode(['success'=>true]);
      exit;
    } else {
      $err = $stmt->error;
      $stmt->close();
      $mysqli->rollback();
      error_log('Delete session failed: '.$err);
      echo json_encode(['success'=>false,'message'=>'Failed to delete session']);
      exit;
    }
  } catch (Exception $e) {
    $mysqli->rollback();
    error_log('Delete session exception: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Error deleting session']);
    exit;
  }

} catch (Exception $e) {
  error_log('Delete session outer exception: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
