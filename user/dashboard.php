<?php
session_start();
require_once 'dashboard_functions.php';

// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Obține informațiile utilizatorului și statisticile dashboard-ului
$user = getUserDetailsById($_SESSION['user_id']);
$dashboardStats = getUserDashboardStats($_SESSION['user_id']);

// Funcție pentru a obține cursurile utilizatorului
function getUserCourses($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.image,
               COUNT(DISTINCT ch.id) as total_chapters,
               COUNT(DISTINCT CASE WHEN up.completed = 1 THEN up.chapter_id END) as completed_chapters
        FROM courses c
        LEFT JOIN chapters ch ON c.id = ch.course_id
        LEFT JOIN user_progress up ON c.id = up.course_id AND up.user_id = :userId
        GROUP BY c.id
    ");
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$userCourses = getUserCourses($_SESSION['user_id']);


    
?>


<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utilizator</title>
    
    <!-- Link Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pentru iconite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <!-- Link la CSS-ul personalizat -->
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar-ul utilizatorului -->
            <div class="col-md-3 col-lg-2 bg-light sidebar p-4" id="sidebar">
                <div class="text-center mb-4">
    <img src="<?php echo !empty($user['profile_image']) ? '../uploads/profile_images/' . htmlspecialchars($user['profile_image']) : 'placeholder_user.png'; ?>" 
         class="img-fluid rounded-circle" 
         alt="User Photo" 
         id="userPhoto"
         style="width: 150px; height: 150px; object-fit: cover;">
    <h4 class="mt-2"><?php echo htmlspecialchars($user['username']); ?></h4>
</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profilul meu</a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Comenzile mele</a>
                    </li>
                    <li class="nav-item">
                        <a href="my_payments.php" class="nav-link"><i class="fas fa-credit-card"></i> Platile mele</a>
                    </li>
                    <li class="nav-item">
                        <a href="settings_account.php" class="nav-link"><i class="fas fa-cog"></i> Setarile contului</a>
                    </li>
                    <li class="nav-item">
                        <a href="support_clients.php" class="nav-link"><i class="fas fa-life-ring"></i> Ajutor - Suport Clienti</a>
                    </li>
                    <li class="nav-item mt-3">
                        <a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Iesi din cont</a>
                    </li>
                    <li class="nav-item mt-3">
                        <a href="test.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> test</a>
                    </li>
                </ul>
            </div>
            
            <!-- Continutul principal -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="header d-flex justify-content-between align-items-center bg-white p-3 rounded mb-4 shadow-sm">
                    <h2>Cursurile mele IT</h2>
                    <div class="search-bar">
                        <input type="text" class="form-control" placeholder="Cauta cursuri">
                    </div>
                </div>
                 <?php if (isAdmin($_SESSION['user_id'])): ?>
                    <div class="mb-3">
                        <a href="manage_courses.php" class="btn btn-primary">Gestionare Cursuri</a>
                    </div>
                <?php endif; ?>
                 <div class="row">
                    <?php if (empty($userCourses)): ?>
                        <div class="col-12">
                            <p>Nu sunteți înscris la niciun curs momentan.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userCourses as $course): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <?php if (!empty($course['image'])): ?>
                                        <img src="../uploads/courses_images/<?php echo htmlspecialchars($course['image']); ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($course['title']); ?>"
                                             style="height: 200px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="course.php?id=<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></a>
                                        </h5>
                                        <p class="card-text">
                                            <?php 
                                            $progress = $course['total_chapters'] > 0 
                                                ? ($course['completed_chapters'] / $course['total_chapters']) * 100 
                                                : 0;
                                            ?>
                                            <span class="badge badge-success">
                                                <?php echo round($progress, 0); ?>% Finalizat
                                            </span>
                                            <span class="badge badge-primary">Inscris</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS și alte biblioteci necesare -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Script personalizat pentru funcționalitate -->
    <script>
        $(document).ready(function() {
            $('#toggleSidebar').click(function() {
                $('#sidebar').toggleClass('closed');
            });
        });
    </script>
</body>
</html>
