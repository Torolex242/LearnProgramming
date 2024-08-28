<?php
session_start();
require_once 'dashboard_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['course_id']) || !isset($_GET['chapter_id'])) {
    header('Location: dashboard.php');
    exit();
}

$courseId = $_GET['course_id'];
$chapterId = $_GET['chapter_id'];
$userId = $_SESSION['user_id'];

function getChapterDetails($chapterId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM chapters WHERE id = ?");
    $stmt->execute([$chapterId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getNextChapter($courseId, $currentOrder) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM chapters WHERE course_id = ? AND order_number > ? ORDER BY order_number ASC LIMIT 1");
    $stmt->execute([$courseId, $currentOrder]);
    return $stmt->fetch(PDO::FETCH_COLUMN);
}

function getPreviousChapter($courseId, $currentOrder) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM chapters WHERE course_id = ? AND order_number < ? ORDER BY order_number DESC LIMIT 1");
    $stmt->execute([$courseId, $currentOrder]);
    return $stmt->fetch(PDO::FETCH_COLUMN);
}

function markChapterAsCompleted($userId, $courseId, $chapterId) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO user_progress (user_id, course_id, chapter_id, completed) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE completed = 1");
    return $stmt->execute([$userId, $courseId, $chapterId]);
}

$chapter = getChapterDetails($chapterId);
$nextChapterId = getNextChapter($courseId, $chapter['order_number']);
$previousChapterId = getPreviousChapter($courseId, $chapter['order_number']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_completed'])) {
    markChapterAsCompleted($userId, $courseId, $chapterId);
    header("Location: chapter.php?course_id=$courseId&chapter_id=$chapterId");
    exit();
}

?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($chapter['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><?php echo htmlspecialchars($chapter['title']); ?></h1>
        <div class="chapter-content">
            <?php echo $chapter['content']; ?>
        </div>

        <form method="post" class="mt-3">
            <button type="submit" name="mark_completed" class="btn btn-success">Marchează ca finalizat</button>
        </form>

        <div class="navigation mt-3">
            <?php if ($previousChapterId): ?>
                <a href="chapter.php?course_id=<?php echo $courseId; ?>&chapter_id=<?php echo $previousChapterId; ?>" class="btn btn-primary">Inapoi</a>
            <?php endif; ?>

            <?php if ($nextChapterId): ?>
                <a href="chapter.php?course_id=<?php echo $courseId; ?>&chapter_id=<?php echo $nextChapterId; ?>" class="btn btn-primary">Urmatorul</a>
            <?php else: ?>
                <a href="course.php?id=<?php echo $courseId; ?>" class="btn btn-success">Finalizează cursul</a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>