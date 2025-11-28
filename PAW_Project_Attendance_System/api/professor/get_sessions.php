<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $course_id = intval($_GET['course_id'] ?? 0);
  $group_id = intval($_GET['group_id'] ?? 0);

  if ($group_id > 0) {
    // verify ownership and fetch group info
    $stmt = $mysqli->prepare('SELECT g.course_id, g.group_name, c.professor_id FROM groups g JOIN courses c ON g.course_id = c.id WHERE g.id = ?');
    if (!$stmt) { error_log('Prepare failed (group ownership check): ' . $mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
    $stmt->bind_param('i', $group_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Group not found']); exit; }
    $row = $res->fetch_assoc();
    if ($row['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
    $group_info = ['id' => $group_id, 'course_id' => intval($row['course_id']), 'group_name' => $row['group_name']];
    $stmt->close();

    // try to select session_time; if that column isn't present try `time`
    $stmt = $mysqli->prepare("SELECT s.id, s.name as session_name, s.session_date, s.session_time, s.created_at FROM sessions s WHERE s.group_id = ? ORDER BY s.session_date DESC, s.session_time DESC");
    if (!$stmt && $mysqli->errno === 1054) {
      $stmt = $mysqli->prepare("SELECT s.id, s.name as session_name, s.session_date, s.`time` AS session_time, s.created_at FROM sessions s WHERE s.group_id = ? ORDER BY s.session_date DESC, s.`time` DESC");
    }
    if (!$stmt) { error_log('Prepare failed (select sessions by group): ' . $mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
    $stmt->bind_param("i", $group_id);
  } else {
    if ($course_id <= 0) { echo json_encode(['success'=>false,'message'=>'Missing course_id']); exit; }
    $stmt = $mysqli->prepare("SELECT s.id, s.name as session_name, s.session_date, s.session_time, s.created_at FROM sessions s WHERE s.course_id = ? AND (s.group_id IS NULL OR s.group_id = 0) ORDER BY s.session_date DESC, s.session_time DESC");
    if (!$stmt && $mysqli->errno === 1054) {
      $stmt = $mysqli->prepare("SELECT s.id, s.name as session_name, s.session_date, s.`time` AS session_time, s.created_at FROM sessions s WHERE s.course_id = ? AND (s.group_id IS NULL OR s.group_id = 0) ORDER BY s.session_date DESC, s.`time` DESC");
    }
    if (!$stmt) { error_log('Prepare failed (select sessions by course): ' . $mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
    $stmt->bind_param("i", $course_id);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  
  $sessions = [];
  while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
  }

  $response = ['success' => true, 'data' => $sessions];
  if (isset($group_info)) $response['group'] = $group_info;
  echo json_encode($response);
  $stmt->close();
} catch (Exception $e) {
  error_log("Get sessions error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}
?>
