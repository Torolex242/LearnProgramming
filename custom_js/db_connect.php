<?php
$servername = "localhost";
$username = "u786278366_Admin";
$password = "Crack923f"; // Înlocuiește cu parola reală
$dbname = "u786278366_MyDataBase";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    error_log("Conexiune eșuată: " . $conn->connect_error);
    die("Eroare de conexiune la baza de date. Vă rugăm să încercați mai târziu.");
}

// Verifică dacă tabela 'messages' există
$result = $conn->query("SHOW TABLES LIKE 'messages'");
if($result->num_rows == 0) {
    // Tabela nu există, o creăm
    $sql = "CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if (!$conn->query($sql)) {
        error_log("Eroare la crearea tabelei 'messages': " . $conn->error);
        die("Eroare la configurarea bazei de date. Vă rugăm să contactați administratorul.");
    }
}
?>