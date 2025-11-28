<?php
header('Content-Type: text/plain; charset=utf-8');
echo "Diagnostic whoami.php\n";
echo "PHP executed file: " . __FILE__ . "\n";
echo "Current working dir: " . getcwd() . "\n\n";
$checkPaths = [
  __DIR__ . '/professor/dashboard.html',
  getcwd() . '/professor/dashboard.html',
  getcwd() . '/simple-website-build/professor/dashboard.html',
  'C:/xampp/htdocs/simple-website-build/professor/dashboard.html',
  'C:/xampp/htdocs/professor/dashboard.html',
];
foreach ($checkPaths as $p) {
  echo "Checking: $p\n";
  if (file_exists($p)) {
    echo "  EXISTS";
    $mtime = filemtime($p);
    echo "  mtime=" . date('c', $mtime) . "\n";
    $contents = @file_get_contents($p);
    if ($contents !== false) {
      $preview = substr($contents, 0, 400);
      echo "  preview:\n";
      echo preg_replace('/\r\n/', "\n", $preview) . "\n";
    }
  } else {
    echo "  MISSING\n";
  }
  echo "\n";
}

echo "Server environment:\n";
echo "DOCUMENT_ROOT=" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME=" . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";

?>