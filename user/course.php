<?php
session_start();

ini_set('log_errors', 'On');
ini_set('error_log', 'debug.log');
error_reporting(E_ALL);

require_once 'dashboard_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$courseId = $_GET['id'];
$userId = $_SESSION['user_id'];
$chapterId = $_GET['chapter_id'] ?? null;

function getCourseDetails($courseId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getChapters($courseId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM chapters WHERE course_id = ? ORDER BY order_number");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCurrentChapter($chapters, $chapterId) {
    if ($chapterId) {
        foreach ($chapters as $index => $chapter) {
            if ($chapter['id'] == $chapterId) {
                return [
                    'chapter' => $chapter,
                    'index' => $index,
                    'prev' => $index > 0 ? $chapters[$index - 1]['id'] : null,
                    'next' => $index < count($chapters) - 1 ? $chapters[$index + 1]['id'] : null
                ];
            }
        }
    }
    return [
        'chapter' => $chapters[0],
        'index' => 0,
        'prev' => null,
        'next' => count($chapters) > 1 ? $chapters[1]['id'] : null
    ];
}

function getUserProgress($userId, $courseId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT chapter_id FROM user_progress WHERE user_id = ? AND course_id = ? AND completed = 1");
    $stmt->execute([$userId, $courseId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getComments($chapterId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, u.username
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.chapter_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$chapterId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculateProgress($completedChapters, $totalChapters) {
    return $totalChapters > 0 ? round((count($completedChapters) / $totalChapters) * 100) : 0;
}

$course = getCourseDetails($courseId);
$chapters = getChapters($courseId);
$currentChapterInfo = getCurrentChapter($chapters, $chapterId);
$currentChapter = $currentChapterInfo['chapter'];
$completedChapters = getUserProgress($userId, $courseId);
$comments = getComments($currentChapter['id']);
$progress = calculateProgress($completedChapters, count($chapters));

$availableIdes = json_decode($currentChapter['available_ides'] ?? '[]', true);
if (empty($availableIdes)) {
    $availableIdes = ['python']; // Setează Python ca default dacă nu există alte opțiuni
}
$hints = json_decode($currentChapter['hints'] ?? '[]', true);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.30.1/min/vs/loader.js"></script>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #1e1e1e;
            color: #ffffff;
        }
        .navbar {
            background-color: #000000;
            padding: 10px 20px;
        }
        .main-container {
            display: flex;
            height: calc(100vh - 56px);
        }
        .sidebar {
            width: 300px;
            background-color: #252526;
            padding: 20px;
            overflow-y: auto;
        }
        .content-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .ide-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .ide-header {
            background-color: #3c3c3c;
            padding: 5px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #editor {
            flex-grow: 1;
            min-height: 400px;
            border: 1px solid #ccc;
        }
        .query-results {
            height: 200px;
            background-color: #1e1e1e;
            border-top: 1px solid #3c3c3c;
            padding: 10px;
            overflow-y: auto;
        }
        .chapter-content {
            padding: 20px;
            overflow-y: auto;
        }
        .hint-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #2d2d2d;
            border-radius: 5px;
        }
        .navigation {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background-color: #2d2d2d;
        }
        .nav-button {
            padding: 5px 15px;
            background-color: #0078d4;
            color: white;
            border: none;
            cursor: pointer;
        }
        .nav-button:disabled {
            background-color: #555;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <span>My Home</span>
        <span>Syllabus</span>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
    </nav>
    
    <div class="main-container">
        <div class="sidebar">
            <h2>Learn</h2>
            <div class="chapter-content">
                <h3><?php echo htmlspecialchars($currentChapter['title']); ?></h3>
                <?php echo $currentChapter['content']; ?>
            </div>
            
            <div class="hint-box" id="hint-text"></div>
            
            <button id="hint-button" class="btn btn-secondary mt-3">Hint</button>
        </div>
        
        <div class="content-area">
            <div class="ide-container">
                <div class="ide-header">
                    <span>IDE - <?php echo htmlspecialchars($currentChapter['title']); ?></span>
                    <select id="ide-selector" class="form-select" style="width: auto;">
                        <?php foreach ($availableIdes as $ide): ?>
                            <option value="<?php echo htmlspecialchars($ide); ?>"><?php echo ucfirst(htmlspecialchars($ide)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="editor"></div>
            </div>
            
            <div class="query-results">
                <h4>Query Results</h4>
                <p>Run a query to see results.</p>
            </div>
            
            <div class="navigation">
                <button id="prev-chapter" class="nav-button" <?php echo $currentChapterInfo['prev'] ? '' : 'disabled'; ?>>Back</button>
                <button id="verify-code" class="nav-button">Verifică codul</button>
                <button id="next-chapter" class="nav-button" <?php echo $currentChapterInfo['next'] ? '' : 'disabled'; ?>>Next</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.30.1/min/vs' }});
            require(['vs/editor/editor.main'], function() {
                window.editor = monaco.editor.create(document.getElementById('editor'), {
                    value: '# Scrie codul tău aici\n',
                    language: '<?php echo htmlspecialchars($availableIdes[0]); ?>',
                    theme: 'vs-dark',
                    automaticLayout: true
                });

                document.getElementById('ide-selector').addEventListener('change', function() {
                    monaco.editor.setModelLanguage(window.editor.getModel(), this.value);
                });

                window.addEventListener('resize', function() {
                    window.editor.layout();
                });
            });
        });

        let currentHintIndex = 0;
        const hints = <?php echo json_encode($hints); ?>;

        document.getElementById('hint-button').addEventListener('click', function() {
            if (currentHintIndex < hints.length) {
                document.getElementById('hint-text').innerHTML += hints[currentHintIndex] + '<br>';
                currentHintIndex++;
            }
        });

        function verifyCode() {
            const userCode = window.editor.getValue();
            const expectedCode = <?php echo json_encode($currentChapter['expected_code']); ?>;
            
            if (userCode.trim() === expectedCode.trim()) {
                alert('Super! Acum poți trece mai departe. Felicitări!');
                document.getElementById('next-chapter').disabled = false;
            } else {
                alert('Mai încearcă');
            }
        }

        document.getElementById('verify-code').addEventListener('click', verifyCode);

        document.getElementById('next-chapter').addEventListener('click', function() {
            if (!this.disabled) {
                window.location.href = 'ide.php?id=<?php echo $courseId; ?>&chapter_id=<?php echo $currentChapterInfo['next']; ?>';
            }
        });

        document.getElementById('prev-chapter').addEventListener('click', function() {
            window.location.href = 'ide.php?id=<?php echo $courseId; ?>&chapter_id=<?php echo $currentChapterInfo['prev']; ?>';
        });
    </script>
</body>
</html>