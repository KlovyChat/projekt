<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$task_id = $_POST['id'] ?? $_GET['id'] ?? null;

if (!$task_id || !is_numeric($task_id)) {
    die("Błąd: Brak poprawnego ID zadania.");
}

try {
    $stmt = $conn->prepare("SELECT status FROM tasks WHERE id = :task_id");
    $stmt->execute(['task_id' => $task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        die("Błąd: Zadanie nie istnieje.");
    }

    $new_status = ($task['status'] == 'do zrobienia') ? 'w trakcie' : 
                  (($task['status'] == 'w trakcie') ? 'zrobione' : 'do zrobienia');

    $stmt = $conn->prepare("UPDATE tasks SET status = :new_status WHERE id = :task_id");
    $stmt->execute(['new_status' => $new_status, 'task_id' => $task_id]);

    $stmt = $conn->prepare("INSERT INTO task_logs (task_id, user_id, action) VALUES (:task_id, :user_id, :action)");
    $stmt->execute([
        'task_id' => $task_id,
        'user_id' => $user_id,
        'action' => "Zmiana statusu na '$new_status'"
    ]);

    header("Location: ../dashboard.php");
    exit;

} catch (PDOException $e) {
    die("Błąd bazy danych: " . $e->getMessage());
}
?>
