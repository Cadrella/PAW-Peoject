<?php
header('Content-Type: application/json');
require_once '../../config/auth.php';

check_auth();
check_role(['professor']);

// This endpoint has been deprecated in this project. Use the per-row
// `api/professor/mark_attendance.php` endpoint which inserts/updates
// attendance rows directly (including the `status2` column for participation).

echo json_encode(['success' => false, 'message' => 'Deprecated endpoint. Use api/professor/mark_attendance.php to save per-row attendance.']);

?>
