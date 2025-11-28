<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $data = json_decode(file_get_contents('php://input'), true);
  $group_id = intval($data['group_id'] ?? 0);
  if ($group_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid group id']); exit; }

  // Verify ownership: group -> course -> professor
  $stmt = $mysqli->prepare('SELECT c.professor_id FROM groups g JOIN courses c ON g.course_id = c.id WHERE g.id = ?');
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Group not found']); exit; }
  $row = $res->fetch_assoc();
  if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
  $stmt->close();
  // Ensure the group is empty (no enrollments / no sessions) before deleting
  // Check enrollments
  $stmt = $mysqli->prepare('SELECT COUNT(*) AS cnt FROM enrollments WHERE group_id = ?');
  if (!$stmt) { error_log('Prepare failed (count enrollments): '.$mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $enrollCount = 0;
  if ($res && $row = $res->fetch_assoc()) { $enrollCount = intval($row['cnt']); }
  $stmt->close();

  // Check sessions in new table
  $stmt = $mysqli->prepare('SELECT COUNT(*) AS cnt FROM sessions WHERE group_id = ?');
  if (!$stmt) { error_log('Prepare failed (count sessions): '.$mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $sessionCount = 0;
  if ($res && $row = $res->fetch_assoc()) { $sessionCount = intval($row['cnt']); }
  $stmt->close();

  // Also check legacy attendance_sessions if present
  $legacyCount = 0;
  $stmt = $mysqli->prepare('SELECT COUNT(*) AS cnt FROM attendance_sessions WHERE group_id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $group_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) { $legacyCount = intval($row['cnt']); }
    $stmt->close();
  }

  if ($enrollCount > 0 || $sessionCount > 0 || $legacyCount > 0) {
    $parts = [];
    if ($enrollCount > 0) $parts[] = "$enrollCount student(s)";
    if ($sessionCount > 0) $parts[] = "$sessionCount session(s)";
    if ($legacyCount > 0) $parts[] = "$legacyCount legacy session(s)";
    $msg = 'Group is not empty: contains ' . implode(', ', $parts) . '. Remove students and sessions first.';
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
  }

  // Safe to delete the group (no students/sessions)
  $stmt = $mysqli->prepare('DELETE FROM groups WHERE id = ?');
  if (!$stmt) { error_log('Prepare failed (delete group): '.$mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
  $stmt->bind_param('i', $group_id);
  if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success'=>true]);
    exit;
  } else {
    $err = $stmt->error;
    $stmt->close();
    error_log('Delete group failed: '.$err);
    echo json_encode(['success'=>false,'message'=>'Failed to delete group']);
    exit;
  }

} catch (Exception $e) {
  error_log('Delete group outer exception: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error']);
}

?>
