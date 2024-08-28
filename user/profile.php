<?php
session_start();
require_once 'dashboard_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user = getUserDetailsById($_SESSION['user_id']);
if (!$user) {
    die("Nu s-au putut obține informațiile utilizatorului.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizare și validare input
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $full_name = sanitizeInput($_POST['full_name']);
    $birthdate = sanitizeInput($_POST['birthdate']);
    $gender = sanitizeInput($_POST['gender']);
    $description = sanitizeInput($_POST['description']);

    // Validare
    $errors = [];
    if (!isUsernameUnique($username, $_SESSION['user_id'])) {
        $errors[] = "Acest nume de utilizator este deja folosit.";
    }
    if (!isEmailUnique($email, $_SESSION['user_id'])) {
        $errors[] = "Această adresă de email este deja folosită.";
    }
    if (!validateEmail($email)) {
        $errors[] = "Adresa de email nu este validă.";
    }
    if (!empty($birthdate) && !validateDate($birthdate)) {
        $errors[] = "Formatul datei de naștere nu este valid.";
    }

    // Procesare imagine profil dacă există
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $imageFileName = processProfileImage($_FILES['profile_image'], $_SESSION['user_id']);
        if ($imageFileName) {
            updateProfileImage($_SESSION['user_id'], $imageFileName);
        } else {
            $errors[] = "Eroare la încărcarea imaginii de profil.";
        }
    }

    if (empty($errors)) {
        // Actualizare profil
        $result = updateUserProfile($_SESSION['user_id'], $username, $full_name, $email, $birthdate, $gender, $description);
        if ($result) {
            $message = "Profilul a fost actualizat cu succes!";
            // Reîncărcăm informațiile utilizatorului
            $user = getUserDetailsById($_SESSION['user_id']);
        } else {
            $message = "A apărut o eroare la actualizarea profilului.";
        }
    } else {
        $message = "Au apărut următoarele erori:<br>" . implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editare Profil</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .profile-form { background-color: white; border-radius: 15px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .profile-picture { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="profile-form p-4">
                    <h1 class="text-center mb-4"><i class="fas fa-user-edit"></i> Editare Profil</h1>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo strpos($message, 'succes') !== false ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="post" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="<?php echo $user['profile_image'] ? '../uploads/profile_images/' . $user['profile_image'] : 'https://via.placeholder.com/150'; ?>" 
                                 alt="Profile Picture" class="profile-picture mb-2">
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">
                                    <i class="fas fa-camera"></i> Schimbă imaginea de profil
                                </label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="fas fa-user"></i> Nume utilizator</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label"><i class="fas fa-id-card"></i> Nume complet</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="birthdate" class="form-label"><i class="fas fa-birthday-cake"></i> Data nașterii</label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($user['birthdate']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="gender" class="form-label"><i class="fas fa-venus-mars"></i> Sex</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Selectează</option>
                                <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>Masculin</option>
                                <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>Feminin</option>
                                <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>>Altul</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label"><i class="fas fa-info-circle"></i> Descriere</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($user['description']); ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizează Profilul</button>
                        </div>
                    </form>
                </div>
                <div class="text-center mt-3">
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Înapoi la Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
