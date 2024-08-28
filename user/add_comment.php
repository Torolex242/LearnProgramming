<?php
session_start(); // Pornirea sesiunii

// Include funcțiile necesare
require_once 'dashboard_functions.php';

// Configurarea erorilor pentru debugging
ini_set('log_errors', 'On');
ini_set('error_log', 'debug.log');
error_reporting(E_ALL);

// Funcție pentru logare într-un fișier debug.log
function log_message($message) {
    $logFile = 'debug.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Logare mesaje de debugging
log_message("POST data: " . json_encode($_POST));
log_message("SESSION data: " . json_encode($_SESSION));

// Verificarea metodei de solicitare
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Preluarea datelor de intrare
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $chapterId = isset($_POST['chapter_id']) ? intval($_POST['chapter_id']) : null;
    $content = isset($_POST['comment']) ? trim($_POST['comment']) : null;

    // Verificare date de intrare
    if ($userId && $chapterId && $content) {
        // Apelarea funcției de adăugare comentariu
        if (addComment($userId, $chapterId, $content)) {
            log_message("Comentariu adăugat cu succes.");
            echo "Comentariul a fost adăugat cu succes.";
        } else {
            log_message("Eroare la adăugarea comentariului.");
            echo "A apărut o eroare la adăugarea comentariului.";
        }
    } else {
        log_message("Date de intrare incomplete.");
        echo "Toate câmpurile sunt obligatorii.";
    }
} else {
    log_message("Metodă de solicitare incorectă.");
    echo "Metodă de solicitare incorectă.";
}
?>
