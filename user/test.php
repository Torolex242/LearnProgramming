<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

echo "<!-- Debugging: Script started -->\n";

if (file_exists('dashboard_functions.php')) {
    require_once 'dashboard_functions.php';
    echo "<!-- Debugging: dashboard_functions.php included -->\n";
} else {
    echo "<!-- Error: dashboard_functions.php not found -->\n";
}

if (!isset($_SESSION['user_id'])) {
    echo "<!-- Warning: user_id not set in session -->\n";
}

if (!isset($_GET['id'])) {
    echo "<!-- Warning: 'id' not set in GET parameters -->\n";
}

$courseId = $_GET['id'] ?? 'undefined';
echo "<!-- Debugging: CourseID = $courseId -->\n";

// Simplifică funcțiile de bază de date
function getCourseDetails($courseId) {
    echo "<!-- Debugging: Attempting to get course details -->\n";
    return ['title' => 'Test Course'];
}

$course = getCourseDetails($courseId);
echo "<!-- Debugging: Course title = " . htmlspecialchars($course['title']) . " -->\n";
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IDE Debug</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.30.1/min/vs/loader.js"></script>
    <style>
        #editor { width: 800px; height: 400px; border: 1px solid #ccc; margin: 20px auto; }
    </style>
</head>
<body>
    <h1>IDE Debug Page</h1>
    <div id="editor"></div>
    <script>
        require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.30.1/min/vs' }});
        require(['vs/editor/editor.main'], function() {
            var editor = monaco.editor.create(document.getElementById('editor'), {
                value: '# Scrie codul tău aici\n',
                language: 'python',
                theme: 'vs-dark'
            });
        });
    </script>
</body>
</html>