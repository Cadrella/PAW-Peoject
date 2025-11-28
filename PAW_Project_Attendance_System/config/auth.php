<?php
session_start();

function login($email, $password) {
  global $mysqli;
  
  try {
    $stmt = $mysqli->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        return true;
      }
    }
    return false;
  } catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    return false;
  }
}

function _is_api_request() {
  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
  $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
  return (stripos($accept, 'application/json') !== false) || (stripos($contentType, 'application/json') !== false) || (strtolower($xhr) === 'xmlhttprequest');
}

function check_auth() {
  if (!isset($_SESSION['user_id'])) {
    if (_is_api_request()) {
      http_response_code(401);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
    }
    header("Location: /login.html");
    exit;
  }
}

function check_role($allowed_roles) {
  if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    if (_is_api_request()) {
      http_response_code(403);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Forbidden']);
      exit;
    }
    die("Unauthorized access");
  }
}

function logout() {
  session_destroy();
  if (_is_api_request()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out']);
    exit;
  }
  header("Location: /login.html");
  exit;
}
?>
