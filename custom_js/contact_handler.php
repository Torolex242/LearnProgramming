<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Funcție pentru logging
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, __DIR__ . '/error.log');
}

try {
    require_once __DIR__ . '/db_connect.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

        logError("Received data: " . json_encode($_POST));

        if (empty($name) || empty($email) || empty($message)) {
            throw new Exception('Toate câmpurile sunt obligatorii.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Adresa de email nu este validă.');
        }

        $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Eroare la pregătirea declarației: " . $conn->error);
        }
        $stmt->bind_param("sss", $name, $email, $message);
        
        if (!$stmt->execute()) {
            throw new Exception("Eroare la executarea declarației: " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            $to = "contact@learnprogrammingfrom0.com";
            $subject = "Mesaj nou de contact";
            $email_message = "Nume: $name\nEmail: $email\nMesaj: $message";
            $headers = "From: noreply@yourwebsite.com";

            if (!mail($to, $subject, $email_message, $headers)) {
                throw new Exception("Eroare la trimiterea email-ului.");
            }

            echo json_encode(['success' => true, 'message' => 'Mesajul a fost trimis cu succes.']);
        } else {
            throw new Exception('Nu s-a putut salva mesajul.');
        }
    } else {
        throw new Exception('Metodă de cerere invalidă.');
    }
} catch (Exception $e) {
    logError("Eroare: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la procesarea cererii: ' . $e->getMessage()]);
}
?>