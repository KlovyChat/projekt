<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$task_text = trim($_POST['task'] ?? '');
$deadline = $_POST['deadline'] ?? null;
$category = $_POST['category'] ?? '';
$priority = $_POST['priority'] ?? '';
$tags_input = trim($_POST['tags'] ?? ''); 

if (empty($task_text)) {
    die("Treść zadania nie może być pusta.");
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO tasks (user_id, task, deadline, category, priority) VALUES (:user_id, :task, :deadline, :category, :priority)");
    $stmt->execute([
        'user_id' => $user_id,
        'task' => $task_text,
        'deadline' => $deadline,
        'category' => $category,
        'priority' => $priority
    ]);

    $task_id = $conn->lastInsertId(); 

    if (!empty($tags_input)) {
        $tags = explode(',', $tags_input);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag == "") continue;

            $stmt = $conn->prepare("SELECT id FROM tags WHERE name = :name");
            $stmt->execute(['name' => $tag]);
            $tag_id = $stmt->fetchColumn();

            if (!$tag_id) {
                $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (:name)");
                $stmt->execute(['name' => $tag]);
                $tag_id = $conn->lastInsertId();
            }

            $stmt = $conn->prepare("SELECT COUNT(*) FROM task_tags WHERE task_id = :task_id AND tag_id = :tag_id");
            $stmt->execute(['task_id' => $task_id, 'tag_id' => $tag_id]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                $stmt = $conn->prepare("INSERT INTO task_tags (task_id, tag_id) VALUES (:task_id, :tag_id)");
                $stmt->execute(['task_id' => $task_id, 'tag_id' => $tag_id]);
            }
        }
    }

    $conn->commit();

    header("Location: ../dashboard.php");
    exit;
} catch (PDOException $e) {
    $conn->rollBack();
    die("Błąd bazy danych: " . $e->getMessage());
}
?>
