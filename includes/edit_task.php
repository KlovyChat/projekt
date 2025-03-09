<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Nieprawidłowe ID zadania.");
}

$task_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

if ($role == 'admin') {
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $task_id]);
} else {
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $task_id, 'user_id' => $user_id]);
}

$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("Nie znaleziono zadania.");
}

$stmt = $conn->prepare("SELECT GROUP_CONCAT(tags.name SEPARATOR ', ') AS tag_list 
                        FROM tags 
                        JOIN task_tags ON tags.id = task_tags.tag_id 
                        WHERE task_tags.task_id = :task_id");
$stmt->execute(['task_id' => $task_id]);
$task['tag_list'] = $stmt->fetchColumn();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_task'])) {
    $task_text = trim($_POST['task']);
    $deadline = $_POST['deadline'] ?? null;
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $tags_input = trim($_POST['tags']);

    if (empty($task_text)) {
        die("Treść zadania nie może być pusta.");
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE tasks SET task = :task, deadline = :deadline, category = :category, priority = :priority WHERE id = :id");
        $stmt->execute([
            'task' => $task_text,
            'deadline' => $deadline,
            'category' => $category,
            'priority' => $priority,
            'id' => $task_id
        ]);

        $stmt = $conn->prepare("DELETE FROM task_tags WHERE task_id = :task_id");
        $stmt->execute(['task_id' => $task_id]);

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

                $stmt = $conn->prepare("INSERT INTO task_tags (task_id, tag_id) VALUES (:task_id, :tag_id)");
                $stmt->execute(['task_id' => $task_id, 'tag_id' => $tag_id]);
            }
        }

        $stmt = $conn->prepare("INSERT INTO task_logs (task_id, user_id, action) VALUES (:task_id, :user_id, :action)");
        $stmt->execute([
            'task_id' => $task_id,
            'user_id' => $user_id,
            'action' => "Zadanie zostało zaktualizowane"
        ]);

        $conn->commit();

        header("Location: ../dashboard.php");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        die("Błąd bazy danych: " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE tasks SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $new_status, 'id' => $task['id']]);

    $stmt = $conn->prepare("INSERT INTO task_logs (task_id, user_id, action) VALUES (:task_id, :user_id, :action)");
    $stmt->execute([
        'task_id' => $task_id,
        'user_id' => $user_id,
        'action' => "Status zadania zmieniono na '$new_status'"
    ]);

    header("Location: ../dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj zadanie</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">
    <h2>Edytuj zadanie</h2>

    <form method="POST">
        <label for="task">Treść zadania:</label>
        <input type="text" name="task" id="task" value="<?= htmlspecialchars($task['task']) ?>" required>

        <label for="deadline">Termin:</label>
        <input type="datetime-local" name="deadline" id="deadline" value="<?= $task['deadline'] ?>">

        <label for="category">Kategoria:</label>
        <select name="category" id="category">
            <option value="Praca" <?= $task['category'] == "Praca" ? "selected" : "" ?>>Praca</option>
            <option value="Dom" <?= $task['category'] == "Dom" ? "selected" : "" ?>>Dom</option>
            <option value="Zakupy" <?= $task['category'] == "Zakupy" ? "selected" : "" ?>>Zakupy</option>
        </select>

        <label for="priority">Priorytet:</label>
        <select name="priority" id="priority">
            <option value="niski" <?= $task['priority'] == "niski" ? "selected" : "" ?>>Niski</option>
            <option value="średni" <?= $task['priority'] == "średni" ? "selected" : "" ?>>Średni</option>
            <option value="wysoki" <?= $task['priority'] == "wysoki" ? "selected" : "" ?>>Wysoki</option>
        </select>

        <label for="tags">Tagi (oddzielone przecinkami):</label>
        <input type="text" name="tags" id="tags" value="<?= htmlspecialchars($task['tag_list'] ?? '') ?>" placeholder="Tagi">

        <button type="submit" name="update_task" class="btn">Zapisz zmiany</button>
    </form>

    <h3>Zmień status zadania</h3>
    <form method="POST">
        <label for="status">Status:</label>
        <select name="status" id="status">
            <option value="do zrobienia" <?= ($task['status'] == 'do zrobienia') ? 'selected' : '' ?>>❌ Do zrobienia</option>
            <option value="w trakcie" <?= ($task['status'] == 'w trakcie') ? 'selected' : '' ?>>🕓 W trakcie</option>
            <option value="zrobione" <?= ($task['status'] == 'zrobione') ? 'selected' : '' ?>>✅ Zrobione</option>
        </select>
        <button type="submit" name="update_status" class="btn">Zapisz</button>
    </form>

    <a href="../dashboard.php" class="btn">Wróć</a>
</div>

</body>
</html>
