<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

try {
  $data = json_decode(file_get_contents("php://input"), true);

  $course_id = intval($data['course_id'] ?? 0);
  $group_id = isset($data['group_id']) ? intval($data['group_id']) : null;
  $session_name = trim($data['session_name'] ?? '');
  $session_date = trim($data['session_date'] ?? '');
  $session_time = trim($data['session_time'] ?? '');

  // basic validation
  if ($session_name === '' || $session_date === '' || $session_time === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'session_name, session_date and session_time are required']);
    exit;
  }

  // validate ownership: if group_id provided verify professor owns the group/course
  if ($group_id && $group_id > 0) {
    $stmt = $mysqli->prepare('SELECT g.course_id, c.professor_id FROM groups g JOIN courses c ON g.course_id = c.id WHERE g.id = ?');
    if (!$stmt) { error_log('Prepare failed (group check): ' . $mysqli->error); echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
    $stmt->bind_param('i', $group_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) { echo json_encode(['success' => false, 'message' => 'Group not found']); exit; }
    $r = $res->fetch_assoc();
    if ($r['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
    $course_id = intval($r['course_id']);
    $stmt->close();
  } else {
    // verify professor owns the course
    if ($course_id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid course']); exit; }
    $stmt = $mysqli->prepare('SELECT professor_id FROM courses WHERE id = ?');
    if (!$stmt) { error_log('Prepare failed (course check): ' . $mysqli->error); echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) { echo json_encode(['success' => false, 'message' => 'Course not found']); exit; }
    $r = $res->fetch_assoc();
    if ($r['professor_id'] != $_SESSION['user_id']) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
    $stmt->close();
  }

  // Insert into new `sessions` table. course_id must be available (either from group or provided)
  if ($group_id && $group_id > 0) {
    // group ownership already verified earlier and course_id set from group
  } else {
    if ($course_id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid course']); exit; }
  }

  // Prepare and insert: try using `session_time` column first, fall back to `time` if needed
  if ($group_id && $group_id > 0) {
    $stmt = $mysqli->prepare("INSERT INTO sessions (course_id, group_id, name, session_date, session_time) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt && $mysqli->errno === 1054) {
      // unknown column 'session_time' - try column named `time`
      $stmt = $mysqli->prepare("INSERT INTO sessions (course_id, group_id, name, session_date, `time`) VALUES (?, ?, ?, ?, ?)");
    }
    if (!$stmt) { error_log('Prepare failed (insert into sessions with group): ' . $mysqli->error); echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
    $stmt->bind_param("iisss", $course_id, $group_id, $session_name, $session_date, $session_time);
  } else {
    $stmt = $mysqli->prepare("INSERT INTO sessions (course_id, group_id, name, session_date, session_time) VALUES (?, NULL, ?, ?, ?)");
    if (!$stmt && $mysqli->errno === 1054) {
      $stmt = $mysqli->prepare("INSERT INTO sessions (course_id, group_id, name, session_date, `time`) VALUES (?, NULL, ?, ?, ?)");
    }
    if (!$stmt) { error_log('Prepare failed (insert into sessions without group): ' . $mysqli->error); echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
    $stmt->bind_param("isss", $course_id, $session_name, $session_date, $session_time);
  }

  if ($stmt->execute()) {
    $newId = $mysqli->insert_id;
    echo json_encode(['success' => true, 'message' => 'Session created', 'session_id' => $newId]);
  } else {
    error_log('Create session failed (sessions table): ' . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create session']);
  }
  $stmt->close();
} catch (Exception $e) {
  error_log("Create session error: " . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error']);
}
?>
