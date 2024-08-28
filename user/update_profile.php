<?php
session_start();
require_once '../functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $description = $_POST['description'];

    // Validare și sanitizare
    $username = sanitizeInput($username);
    $full_name = sanitizeInput($full_name);
    $email = sanitizeInput($email);
    $description = sanitizeInput($description);

    // Actualizează informațiile profilului
    $success = updateUserProfile($userId, $username, $full_name, $email, $birthdate, $gender, $description);

    // Procesează încărcarea imaginii de profil
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profile_images/';
        $fileName = $userId . '_' . basename($_FILES['profile_image']['name']);
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
            // Actualizează calea imaginii de profil în baza de date
            updateProfileImage($userId, $fileName);
        }
    }

    if ($success) {
        $_SESSION['message'] = 'Profilul a fost actualizat cu succes!';
    } else {
        $_SESSION['error'] = 'A apărut o eroare la actualizarea profilului.';
    }

    header('Location: profile.php');
    exit();
}
?>