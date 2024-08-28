<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

function updateUserProfile($pdo, $userId, $username, $email, $full_name, $birthdate, $gender, $description) {
    try {
        // Verificăm dacă username-ul și email-ul sunt unice (exceptând utilizatorul curent)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = :username OR email = :email) AND id != :userId");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':userId' => $userId
        ]);
        if ($stmt->fetchColumn() > 0) {
            // Numele de utilizator sau email-ul sunt deja folosite
            return false;
        }

        // Pregătim query-ul de actualizare
        $sql = "UPDATE users SET 
                username = :username, 
                email = :email, 
                full_name = :full_name, 
                birthdate = :birthdate, 
                gender = :gender, 
                description = :description 
                WHERE id = :userId";
        
        $stmt = $pdo->prepare($sql);
        
        // Executăm query-ul cu parametrii
        $result = $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':full_name' => $full_name,
            ':birthdate' => $birthdate ?: null,
            ':gender' => $gender,
            ':description' => $description,
            ':userId' => $userId
        ]);

        return $result;
    } catch (PDOException $e) {
        // Loghează eroarea pentru debugging
        error_log("Eroare la actualizarea profilului: " . $e->getMessage());
        return false;
    }
}

function getUserDetailsById($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Eroare în getUserDetailsById: " . $e->getMessage());
        return false;
    }
}

function getUserById($userId) {
    global $pdo; // Asigură-te că variabila $pdo este disponibilă

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error_log("Nu s-a găsit niciun utilizator cu ID-ul: " . $userId);
            return false;
        }

        return $user;
    } catch (PDOException $e) {
        error_log("Eroare la obținerea utilizatorului: " . $e->getMessage());
        return false;
    }
}

function getUserByUsername($pdo, $username) {
    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function authenticate_user($username, $password) {
    global $pdo;
    $user = getUserByUsername($pdo, $username);

    if ($user && password_verify($password, $user['password'])) {
        return true;
    }
    return false;
}

function register_user($username, $email, $password) {
    global $pdo;
    
    error_log("Attempting to register user: $username, $email");

    $sql = "SELECT id FROM users WHERE username = :username OR email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username, 'email' => $email]);

    if ($stmt->rowCount() > 0) {
        error_log("User or email already exists");
        return false;
    }

    $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
    $stmt = $pdo->prepare($sql);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    if ($stmt->execute(['username' => $username, 'email' => $email, 'password' => $hashed_password])) {
        error_log("User registered successfully");
        return true;
    } else {
        error_log("SQL error in register_user (insert): " . implode(", ", $stmt->errorInfo()));
        return false;
    }
}

function send_reset_link($email) {
    global $pdo;

    $token = bin2hex(random_bytes(16));
    $reset_link = "https://learnprogrammingfrom0.com/reset_password.php?token=" . $token;

    if (save_token_to_db($email, $token)) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'contact@learnprogrammingfrom0.com';
            $mail->Password   = 'Piewuj3@#';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('contact@learnprogrammingfrom0.com', 'Learn Programming');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Resetează parola ta';
            $mail->Body    = "Pentru a-ți reseta parola, accesează următorul link: <a href='$reset_link'>$reset_link</a>";
            $mail->AltBody = "Pentru a-ți reseta parola, accesează următorul link: $reset_link";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error sending password reset email to $email: " . $mail->ErrorInfo);
            return false;
        }
    } else {
        error_log("Error saving token to database for $email");
        return false;
    }
}

function save_token_to_db($email, $token) {
    global $pdo;

    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
        $stmt->execute(['email' => $email]);

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)");
        $stmt->execute(['email' => $email, 'token' => $token, 'expires_at' => $expires_at]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in save_token_to_db: " . $e->getMessage());
        return false;
    }
}

function is_token_valid($token) {
    global $pdo;
    $current_time = date('Y-m-d H:i:s');
    $sql = "SELECT * FROM password_resets WHERE token = :token AND expires_at > :current_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['token' => $token, 'current_time' => $current_time]);
    return $stmt->rowCount() > 0;
}

function reset_password($token, $new_password) {
    global $pdo;
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $pdo->rollBack();
            return false;
        }

        $email = $result['email'];
        
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
        $stmt->execute(['password' => $hashed_password, 'email' => $email]);

        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $token]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in reset_password: " . $e->getMessage());
        return false;
    }
}
?>