<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'functions.php';

$response = ["success" => false, "message" => ""];

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception("Metoda de cerere invalidă.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
    }

    if (empty($username) || empty($email) || empty($password)) {
        throw new Exception("Toate câmpurile sunt obligatorii.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Adresa de email nu este validă.");
    }

    if (strlen($password) < 8) {
        throw new Exception("Parola trebuie să aibă cel puțin 8 caractere.");
    }

    if (register_user($username, $email, $password)) {
        $response["success"] = true;
        $response["message"] = "Înregistrare reușită! Vă puteți autentifica acum.";
    } else {
        throw new Exception("Eroare la înregistrare. Numele de utilizator sau adresa de email ar putea fi deja în uz.");
    }

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
    error_log("Eroare la înregistrare: " . $e->getMessage());
}

echo json_encode($response);
?>