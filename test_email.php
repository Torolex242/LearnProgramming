<?php
$to = 'bitcoincont340@gmail.com';  // Înlocuiește cu adresa ta de email
$subject = 'Test Email';
$message = 'Acesta este un test pentru trimiterea de email-uri';
$headers = 'From: no-reply@example.com';

if (mail($to, $subject, $message, $headers)) {
    echo 'Email trimis cu succes!';
} else {
    echo 'Trimiterea email-ului a eșuat.';
}
?>