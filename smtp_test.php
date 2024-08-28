<?php
// Definim calea către directorul PHPMailer
$phpmailer_dir = __DIR__ . '/PHPMailer-master/src/';

// Includem fișierele necesare
require $phpmailer_dir . 'Exception.php';
require $phpmailer_dir . 'PHPMailer.php';
require $phpmailer_dir . 'SMTP.php';

// Utilizăm namespace-urile
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

try {
    $mail = new PHPMailer(true);
    
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com'; // Serverul SMTP de la Hostinger
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contact@learnprogrammingfrom0.com'; // Adresa de email
    $mail->Password   = 'Piewuj3@#'; // Parola contului de email
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587; // Portul SMTP

    // Setări email
    $mail->setFrom('contact@learnprogrammingfrom0.com', 'Learn Programming');
    $mail->addAddress('cracknumalasa@gmail.com', 'Nume Destinatar');

    // Conținut email
    $mail->isHTML(true);
    $mail->Subject = 'Test PHPMailer cu Hostinger';
    $mail->Body    = 'Acesta este un email de test trimis prin PHPMailer cu SMTP folosind Hostinger.';

    $mail->send();
    echo 'Mesajul a fost trimis cu succes';
} catch (Exception $e) {
    echo "Mesajul nu a putut fi trimis. Eroare PHPMailer: {$mail->ErrorInfo}";
} catch (\Error $e) {
    echo "A apărut o eroare: " . $e->getMessage();
}
?>