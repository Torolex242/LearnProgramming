<?php
session_start();
require_once 'dashboard_functions.php';

// Verifică dacă utilizatorul este admin
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$courseId = $_GET['id'] ?? null;

// Funcții pentru gestionarea cursurilor și capitolelor
function getCourses() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCourse($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getChapters($courseId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM chapters WHERE course_id = ? ORDER BY order_number");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getChapter($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM chapters WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addCourse($title, $description, $image) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO courses (title, description, image) VALUES (?, ?, ?)");
    return $stmt->execute([$title, $description, $image]);
}

function updateCourse($id, $title, $description, $image) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, image = ? WHERE id = ?");
    return $stmt->execute([$title, $description, $image, $id]);
}

function deleteCourse($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    return $stmt->execute([$id]);
}

function addChapter($courseId, $title, $content, $orderNumber, $availableIdes, $hints) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO chapters (course_id, title, content, order_number, available_ides, hints) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$courseId, $title, $content, $orderNumber, json_encode($availableIdes), json_encode($hints)]);
}

function updateChapter($id, $title, $content, $orderNumber, $availableIdes, $hints) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE chapters SET title = ?, content = ?, order_number = ?, available_ides = ?, hints = ? WHERE id = ?");
    return $stmt->execute([$title, $content, $orderNumber, json_encode($availableIdes), json_encode($hints), $id]);
}

function deleteChapter($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM chapters WHERE id = ?");
    return $stmt->execute([$id]);
}

// Procesarea formularelor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = uploadCourseImage($_FILES['image']);
        }
        addCourse($title, $description, $image);
        header('Location: manage_courses.php');
        exit();
    } elseif (isset($_POST['edit_course'])) {
        $id = $_POST['course_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $course = getCourse($id);
        $image = $course['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = uploadCourseImage($_FILES['image']);
        }
        updateCourse($id, $title, $description, $image);
        header('Location: manage_courses.php');
        exit();
    } elseif (isset($_POST['delete_course'])) {
        $id = $_POST['course_id'];
        deleteCourse($id);
        header('Location: manage_courses.php');
        exit();
    } elseif (isset($_POST['add_chapter'])) {
        $courseId = $_POST['course_id'];
        $title = $_POST['chapter_title'];
        $content = $_POST['chapter_content'];
        $orderNumber = $_POST['order_number'];
        $availableIdes = $_POST['available_ides'] ?? [];
        $hints = $_POST['hints'] ?? [];
        addChapter($courseId, $title, $content, $orderNumber, $availableIdes, $hints);
        header("Location: manage_courses.php?action=edit&id=$courseId");
        exit();
    } elseif (isset($_POST['edit_chapter'])) {
        $chapterId = $_POST['chapter_id'];
        $courseId = $_POST['course_id'];
        $title = $_POST['chapter_title'];
        $content = $_POST['chapter_content'];
        $orderNumber = $_POST['order_number'];
        $availableIdes = $_POST['available_ides'] ?? [];
        $hints = $_POST['hints'] ?? [];
        updateChapter($chapterId, $title, $content, $orderNumber, $availableIdes, $hints);
        header("Location: manage_courses.php?action=edit&id=$courseId");
        exit();
    } elseif (isset($_POST['delete_chapter'])) {
        $chapterId = $_POST['chapter_id'];
        $courseId = $_POST['course_id'];
        deleteChapter($chapterId);
        header("Location: manage_courses.php?action=edit&id=$courseId");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionare Cursuri</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Gestionare Cursuri</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary mb-3">Adaugă Curs Nou</a>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titlu</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (getCourses() as $course): ?>
                        <tr>
                            <td><?php echo $course['id']; ?></td>
                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">Editează</a>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" name="delete_course" class="btn btn-sm btn-danger" onclick="return confirm('Sigur doriți să ștergeți acest curs?')">Șterge</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <?php elseif ($action === 'add'): ?>
            <h2>Adaugă Curs Nou</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Titlu</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Descriere</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Imagine</label>
                    <input type="file" class="form-control-file" id="image" name="image">
                </div>
                <button type="submit" name="add_course" class="btn btn-primary">Adaugă Curs</button>
            </form>
        
        <?php elseif ($action === 'edit' && $courseId): ?>
            <?php $course = getCourse($courseId); ?>
            <h2>Editează Curs</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                <div class="form-group">
                    <label for="title">Titlu</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Descriere</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($course['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Imagine</label>
                    <?php if ($course['image']): ?>
                        <img src="../uploads/courses_images/<?php echo $course['image']; ?>" alt="Course Image" style="max-width: 200px; display: block; margin-bottom: 10px;">
                    <?php endif; ?>
                    <input type="file" class="form-control-file" id="image" name="image">
                </div>
                <button type="submit" name="edit_course" class="btn btn-primary">Actualizează Curs</button>
            </form>

            <h3 class="mt-4">Capitole</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ordine</th>
                        <th>Titlu</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (getChapters($courseId) as $chapter): ?>
                        <tr>
                            <td><?php echo $chapter['order_number']; ?></td>
                            <td><?php echo htmlspecialchars($chapter['title']); ?></td>
                            <td>
                                <a href="?action=edit_chapter&id=<?php echo $chapter['id']; ?>&course_id=<?php echo $courseId; ?>" class="btn btn-sm btn-primary">Editează</a>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="chapter_id" value="<?php echo $chapter['id']; ?>">
                                    <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                    <button type="submit" name="delete_chapter" class="btn btn-sm btn-danger" onclick="return confirm('Sigur doriți să ștergeți acest capitol?')">Șterge</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h4>Adaugă Capitol Nou</h4>
            <form method="post">
                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                <div class="form-group">
                    <label for="chapter_title">Titlu Capitol</label>
                    <input type="text" class="form-control" id="chapter_title" name="chapter_title" required>
                </div>
                <div class="form-group">
                    <label for="chapter_content">Conținut Capitol</label>
                    <textarea class="form-control" id="chapter_content" name="chapter_content" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="order_number">Număr Ordine</label>
                    <input type="number" class="form-control" id="order_number" name="order_number" required>
                </div>
                <div class="form-group">
                    <label>IDE-uri Disponibile</label>
                    <?php
                    $allIdes = ['python', 'javascript', 'java', 'c++'];
                    foreach ($allIdes as $ide) {
                        echo "<div class='form-check'>
                                <input class='form-check-input' type='checkbox' name='available_ides[]' value='$ide' id='new_ide_$ide'>
                                <label class='form-check-label' for='new_ide_$ide'>
                                    " . ucfirst($ide) . "
                                </label>
                              </div>";
                    }
                    ?>
                </div>
                <div class="form-group">
                    <label for="hints">Hint-uri</label>
                    <?php
                    for ($i = 0; $i < 3; $i++) {
                        echo "<textarea class='form-control mb-2' name='hints[]' rows='2' placeholder='Hint " . ($i + 1) . "'></textarea>";
                    }
                    ?>
                </div>
                <button type="submit" name="add_chapter" class="btn btn-success">Adaugă Capitol</button>
            </form>
        
        <?php elseif ($action === 'edit_chapter'): ?>
            <?php 
            $chapterId = $_GET['id'];
            $courseId = $_GET['course_id'];
            $chapter = getChapter($chapterId); 
            ?>
            <h2>Editează Capitol</h2>
            <form method="post">
                <input type="hidden" name="chapter_id" value="<?php echo $chapter['id']; ?>">
                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                <div class="form-group">
                    <label for="chapter_title">Titlu Capitol</label>
                    <input type="text" class="form-control" id="chapter_title" name="chapter_title" value="<?php echo htmlspecialchars($chapter['title']); ?>" required>
                </div>
                
                    <div class="form-group">
                    <label for="chapter_content">Conținut Capitol</label>
                    <textarea class="form-control" id="chapter_content" name="chapter_content" rows="3"><?php echo htmlspecialchars($chapter['content']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="order_number">Număr Ordine</label>
                    <input type="number" class="form-control" id="order_number" name="order_number" value="<?php echo $chapter['order_number']; ?>" required>
                </div>
                <div class="form-group">
                    <label>IDE-uri Disponibile</label>
                    <?php
                    $availableIdes = json_decode($chapter['available_ides'] ?? '[]', true);
                    $allIdes = ['python', 'javascript', 'java', 'c++'];
                    foreach ($allIdes as $ide) {
                        $checked = in_array($ide, $availableIdes) ? 'checked' : '';
                        echo "<div class='form-check'>
                                <input class='form-check-input' type='checkbox' name='available_ides[]' value='$ide' id='ide_$ide' $checked>
                                <label class='form-check-label' for='ide_$ide'>
                                    " . ucfirst($ide) . "
                                </label>
                              </div>";
                    }
                    ?>
                </div>
                <div class="form-group">
                    <label for="hints">Hint-uri</label>
                    <?php
                    $hints = json_decode($chapter['hints'] ?? '[]', true);
                    for ($i = 0; $i < 3; $i++) {
                        $hintValue = isset($hints[$i]) ? htmlspecialchars($hints[$i]) : '';
                        echo "<textarea class='form-control mb-2' name='hints[]' rows='2' placeholder='Hint " . ($i + 1) . "'>$hintValue</textarea>";
                    }
                    ?>
                </div>
                <button type="submit" name="edit_chapter" class="btn btn-primary">Actualizează Capitol</button>
            </form>
        <?php endif; ?>

        <a href="dashboard.php" class="btn btn-secondary mt-3">Înapoi la Dashboard</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>