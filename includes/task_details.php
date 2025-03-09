<?php
session_start();
include __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$task_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$task_id || !is_numeric($task_id)) {
    die("Błąd: Nieprawidłowe ID zadania.");
}

$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = :id");
$stmt->execute(['id' => $task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("Zadanie nie istnieje.");
}

$stmt = $conn->prepare("SELECT c.*, u.username FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.task_id = :task_id 
                        ORDER BY c.created_at DESC");
$stmt->execute(['task_id' => $task_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT l.*, u.username FROM task_logs l 
                        JOIN users u ON l.user_id = u.id 
                        WHERE l.task_id = :task_id 
                        ORDER BY l.created_at DESC");
$stmt->execute(['task_id' => $task_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (:task_id, :user_id, :comment)");
        $stmt->execute(['task_id' => $task_id, 'user_id' => $user_id, 'comment' => $comment]);

        $stmt = $conn->prepare("INSERT INTO task_logs (task_id, user_id, action) VALUES (:task_id, :user_id, 'Dodano komentarz')");
        $stmt->execute(['task_id' => $task_id, 'user_id' => $user_id]);

        header("Location: task_details.php?id=" . urlencode($task_id));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Szczegóły zadania</title>
    <link rel="stylesheet" href="../css/style.css"> 
</head>
<body>

<div class="container">
    <h2><?= htmlspecialchars($task['task']) ?> | <?= htmlspecialchars($task['category']) ?> | <?= htmlspecialchars($task['priority']) ?></h2>
    <p><?= isset($task['description']) ? htmlspecialchars($task['description']) : 'Brak opisu' ?></p>

    <h3>Dodaj komentarz:</h3>
    <form method="POST">
        <textarea name="comment" placeholder="Napisz komentarz..." required></textarea>
        <button type="submit" class="btn">Dodaj</button>
    </form>

    <h3>Komentarze:</h3>
    <ul class="comment-list">
        <?php if (empty($comments)): ?>
            <li>Brak komentarzy.</li>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <li>
                    <strong><?= htmlspecialchars($comment['username']) ?></strong> 
                    <p><?= htmlspecialchars($comment['comment']) ?></p>
                    <small><?= $comment['created_at'] ?></small>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <h3>Historia zmian:</h3>
    <ul class="log-list">
        <?php if (empty($logs)): ?>
            <li>Brak historii zmian.</li>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <li>
                    <strong><?= htmlspecialchars($log['username']) ?></strong> - <?= htmlspecialchars($log['action']) ?>
                    <small><?= $log['created_at'] ?></small>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <a href="../dashboard.php" class="btn">Powrót</a>
</div>

</body>
</html>