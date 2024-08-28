<?php
session_start();
require_once 'dashboard_functions.php';

if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Logica pentru a obține toți utilizatorii
function getAllUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, username, email, is_admin FROM users");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$users = getAllUsers();

// Logica pentru a procesa schimbările de status admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_admin'])) {
    $targetUserId = $_POST['user_id'];
    $newAdminStatus = $_POST['new_status'] === '1';
    toggleAdminStatus($targetUserId, $newAdminStatus);
    header('Location: manage_users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionare Utilizatori</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Gestionare Utilizatori</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Admin</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['is_admin'] ? 'Da' : 'Nu'; ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $user['is_admin'] ? '0' : '1'; ?>">
                                <button type="submit" name="toggle_admin" class="btn btn-sm <?php echo $user['is_admin'] ? 'btn-danger' : 'btn-success'; ?>">
                                    <?php echo $user['is_admin'] ? 'Revocă Admin' : 'Fă Admin'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>