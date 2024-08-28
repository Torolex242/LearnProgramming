
<?php
$servername = "localhost";
$username = "u786278366_Admin";
$password = "Crack923f"; // Înlocuiește cu parola reală
$dbname = "u786278366_MyDataBase";
$port = 3306;

try {
    $dsn = "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Setează variabila globală pentru a fi accesibilă în functions.php
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    error_log("Conexiune eșuată: " . $e->getMessage());
    die("Eroare de conexiune la baza de date. Vă rugăm să încercați mai târziu.");
}
?>