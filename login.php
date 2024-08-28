<?php
session_start();
header('Content-Type: application/json');

error_log('Raw POST data: ' . file_get_contents('php://input'));
error_log('$_POST: ' . print_r($_POST, true));
error_log('$_SERVER: ' . print_r($_SERVER, true));

require 'functions.php';

$response = ['success' => false, 'message' => '', 'redirect' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $email = $input['email'] ?? $input['username'] ?? '';
        $password = $input['password'] ?? '';
    } else {
        $email = $_POST['email'] ?? $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
    }

    error_log('Received email/username: ' . $email);
    error_log('Received password: ' . (empty($password) ? 'empty' : 'not empty'));

    if (empty($email) || empty($password)) {
        throw new Exception('Email/Username and password are required');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    error_log('User found: ' . ($user ? 'yes' : 'no'));

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $response['success'] = true;
        $response['redirect'] = 'user/dashboard.php';
        $response['message'] = 'Login successful';
        error_log('Login successful for user: ' . $email);
    } else {
        $response['message'] = 'Email/Username sau parola este greșită';
        error_log('Login failed for user: ' . $email);
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Login error: ' . $e->getMessage());
}

error_log('Final response: ' . json_encode($response));
echo json_encode($response);
exit;
?>