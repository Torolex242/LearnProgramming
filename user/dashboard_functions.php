<?php
// Asigură-te că acest fișier este inclus în fișierul principal de configurare sau în fiecare pagină de dashboard unde este necesar
require_once '../db.php'; // Fișierul care conține conexiunea la baza de date

/**
 * Obține informațiile complete ale unui utilizator după ID
 */
function getUserDetailsById($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Actualizează profilul utilizatorului
 */
function updateUserProfile($userId, $username, $full_name, $email, $birthdate, $gender, $description) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, birthdate = ?, gender = ?, description = ? WHERE id = ?");
    return $stmt->execute([$username, $full_name, $email, $birthdate, $gender, $description, $userId]);
}

/**
 * Actualizează imaginea de profil a utilizatorului
 */
function updateProfileImage($userId, $imagePath) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    return $stmt->execute([$imagePath, $userId]);
}

/**
 * Verifică dacă un username este deja folosit (exclude utilizatorul curent)
 */
function isUsernameUnique($username, $currentUserId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $currentUserId]);
    return $stmt->fetchColumn() == 0;
}

/**
 * Verifică dacă un email este deja folosit (exclude utilizatorul curent)
 */
function isEmailUnique($email, $currentUserId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $currentUserId]);
    return $stmt->fetchColumn() == 0;
}

/**
 * Sanitizează input-ul utilizatorului
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Validează formatul email-ului
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validează formatul datei
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Procesează și salvează imaginea de profil
 */
function processProfileImage($file, $userId) {
    $uploadDir = '../uploads/profile_images/';
    $fileName = $userId . '_' . basename($file['name']);
    $uploadFile = $uploadDir . $fileName;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }

    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
        return $fileName;
    }

    return false;
}

/**
 * Obține statistici de bază pentru dashboard
 */
function getUserDashboardStats($userId) {
    global $pdo;
    $stats = [
        'total_posts' => 0,
        'total_comments' => 0,
        // Alte statistici pot fi adăugate aici
    ];

    // Verificăm dacă tabela 'posts' există
    $tableExists = $pdo->query("SHOW TABLES LIKE 'posts'")->rowCount() > 0;

    if ($tableExists) {
        // Dacă tabela există, obținem numărul de postări
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total_posts'] = $stmt->fetchColumn();
    }

    // Poți adăuga verificări similare pentru alte tabele și statistici

    return $stats;
}
// Utilizare:
// Pentru a face un utilizator admin:
// toggleAdminStatus($userId, true);

// Pentru a revoca drepturile de admin:
// toggleAdminStatus($userId, false);
function toggleAdminStatus($userId, $isAdmin) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
    return $stmt->execute([$isAdmin, $userId]);
}


function isAdmin($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['is_admin'] == true;
    
}

function getCourseById($courseId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function uploadCourseImage($image) {
    $targetDir = "../uploads/courses_images/";
    $imageFileType = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $imageName = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $imageName;

    // Verificați dacă fișierul este o imagine reală
    $check = getimagesize($image["tmp_name"]);
    if($check === false) {
        throw new Exception("Fișierul nu este o imagine.");
    }

    // Permiteți doar anumite formate de fișiere
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        throw new Exception("Doar fișierele JPG, JPEG, PNG și GIF sunt permise.");
    }

    if (move_uploaded_file($image["tmp_name"], $targetFile)) {
        return $imageName;
    } else {
        throw new Exception("A apărut o eroare la încărcarea imaginii.");
    }
}
function insertCourse($title, $description, $imageName) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO courses (title, description, image) VALUES (?, ?, ?)");
    return $stmt->execute([$title, $description, $imageName]);
}

function addComment($userId, $chapterId, $content) {
    global $pdo; // Folosirea variabilei globale PDO

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, chapter_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $chapterId, $content]);

        if ($stmt->rowCount() > 0) {
            return true; // Comentariul a fost adăugat cu succes
        } else {
            file_put_contents('debug.log', "Nu s-a putut adăuga comentariul.\n", FILE_APPEND);
            return false; // Nu s-a putut adăuga comentariul
        }
    } catch (PDOException $e) {
        file_put_contents('debug.log', "Eroare la adăugarea comentariului: " . $e->getMessage() . "\n", FILE_APPEND);
        return false; // Întoarce false dacă apare o eroare
    }
}
function getCourseIdByChapterId($chapterId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT course_id FROM chapters WHERE id = ?");
    $stmt->execute([$chapterId]);
    return $stmt->fetchColumn();
}
// Poți adăuga aici alte funcții specifice dashboard-ului după necesități
?>