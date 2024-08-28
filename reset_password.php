<?php
require 'functions.php';
// Activăm raportarea erorilor pentru depanare
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (empty($email)) {
            echo json_encode(["success" => false, "message" => "Toate câmpurile sunt obligatorii."]);
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Emailul introdus nu este valid."]);
        } else {
            if (send_reset_link($email)) {
                echo json_encode(["success" => true, "message" => "Un link de resetare a parolei a fost trimis la adresa de email furnizată."]);
            } else {
                echo json_encode(["success" => false, "message" => "Eroare la trimiterea linkului de resetare. Vă rugăm să încercați din nou mai târziu."]);
            }
        }
    } elseif (isset($_POST['token'], $_POST['new_password'], $_POST['confirm_password'])) {
        $token = $_POST['token'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            echo json_encode(["success" => false, "message" => "Parolele nu se potrivesc."]);
        } elseif (strlen($new_password) < 8) {
            echo json_encode(["success" => false, "message" => "Parola trebuie să aibă cel puțin 8 caractere."]);
        } elseif (!is_token_valid($token)) {
            echo json_encode(["success" => false, "message" => "Token invalid sau expirat."]);
        } else {
            if (reset_password($token, $new_password)) {
                echo json_encode(["success" => true, "message" => "Parola a fost resetată cu succes."]);
            } else {
                echo json_encode(["success" => false, "message" => "Eroare la resetarea parolei. Vă rugăm să încercați din nou."]);
            }
        }
    } else {
        echo json_encode(["success" => false, "message" => "Date invalide primite."]);
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
    $token = $_GET['token'];
    // Verifică validitatea token-ului
    if (is_token_valid($token)) {
        // Afișează formularul pentru resetarea parolei
        ?>
        <!DOCTYPE html>
        <html lang="ro">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Resetare Parolă</title>
            <link rel="stylesheet" href="style.css">
        </head>
        <body>
            <div class="container">
                <h2>Resetare Parolă</h2>
                <form id="resetPasswordForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label for="new_password">Noua Parolă:</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmă Noua Parolă:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn">Resetează Parola</button>
                </form>
                <div id="message"></div>
            </div>
            <script>
                document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    fetch('reset_password.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('message').innerText = data.message;
                        if (data.success) {
                            // Redirecționează utilizatorul sau afișează un mesaj de succes
                            setTimeout(() => {
                                window.location.href = 'index.html'; // Schimbați cu pagina dorită
                            }, 3000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('message').innerText = 'A apărut o eroare. Vă rugăm să încercați din nou.';
                    });
                });
            </script>
        </body>
        </html>
        <?php
    } else {
        echo "Token invalid sau expirat. Vă rugăm să solicitați un nou link de resetare a parolei.";
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Metoda de cerere invalidă."]);
}
?>