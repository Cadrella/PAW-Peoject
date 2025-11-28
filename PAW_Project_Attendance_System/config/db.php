<?php
// Database connection
try {
  $mysqli = new mysqli("localhost", "root", "password", "attendance_db");
  
  if ($mysqli->connect_error) {
    error_log("Database connect error: " . $mysqli->connect_error);
    die("Database connection error");
  }
  
  $mysqli->set_charset("utf8mb4");
} catch (Exception $e) {
  error_log("Database connection error: " . $e->getMessage());
  die("Database connection error");
}
?>
