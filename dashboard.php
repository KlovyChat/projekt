<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

$category = $_GET['category'] ?? '';
$priority = $_GET['priority'] ?? '';
$sort = $_GET['sort'] ?? 'deadline';

$sql = "SELECT t.*, 
            GROUP_CONCAT(tags.name SEPARATOR ', ') AS tag_list 
        FROM tasks t
        LEFT JOIN task_tags tt ON t.id = tt.task_id
        LEFT JOIN tags ON tt.tag_id = tags.id
        WHERE t.user_id = :user_id";

$params = ['user_id' => $user_id];

if (!empty($category)) {
    $sql .= " AND category = :category";
    $params['category'] = $category;
}

if (!empty($priority)) {
    $sql .= " AND priority = :priority";
    $params['priority'] = $priority;
}

$sql .= " GROUP BY t.id ORDER BY $sort ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h2>Twoje zadania</h2>
    <a href="auth/logout.php" class="btn">Wyloguj się</a>

    <?php if ($role == 'admin'): ?>
        <p>Jesteś administratorem.</p>
    <?php endif; ?>

    <form action="includes/add_task.php" method="POST">
        <input type="text" name="task" placeholder="Dodaj zadanie" required>
        <input type="datetime-local" name="deadline">
        
        <select name="category">
            <option value="Praca">Praca</option>
            <option value="Dom">Dom</option>
            <option value="Zakupy">Zakupy</option>
        </select>

        <select name="priority">
            <option value="niski">Niski</option>
            <option value="średni">Średni</option>
            <option value="wysoki">Wysoki</option>
        </select>

        <input type="text" name="tags" placeholder="Tagi (oddzielone przecinkami)">

        <button type="submit" class="btn">Dodaj</button>
    </form>

    <form method="GET">
        <label for="category">Kategoria:</label>
        <select name="category">
            <option value="">Wszystkie</option>
            <option value="Praca" <?= $category == 'Praca' ? 'selected' : '' ?>>Praca</option>
            <option value="Dom" <?= $category == 'Dom' ? 'selected' : '' ?>>Dom</option>
            <option value="Zakupy" <?= $category == 'Zakupy' ? 'selected' : '' ?>>Zakupy</option>
        </select>

        <label for="priority">Priorytet:</label>
        <select name="priority">
            <option value="">Wszystkie</option>
            <option value="niski" <?= $priority == 'niski' ? 'selected' : '' ?>>Niski</option>
            <option value="średni" <?= $priority == 'średni' ? 'selected' : '' ?>>Średni</option>
            <option value="wysoki" <?= $priority == 'wysoki' ? 'selected' : '' ?>>Wysoki</option>
        </select>

        <label for="sort">Sortuj według:</label>
        <select name="sort">
            <option value="deadline" <?= $sort == 'deadline' ? 'selected' : '' ?>>Termin</option>
            <option value="priority" <?= $sort == 'priority' ? 'selected' : '' ?>>Priorytet</option>
        </select>

        <button type="submit" class="btn">Filtruj</button>
    </form>

    <h3>Lista zadań:</h3>
    <table>
        <tr>
            <th>Zadanie</th>
            <th>Kategoria</th>
            <th>Priorytet</th>
            <th>Termin</th>
            <th>Status</th>
            <th>Tagi</th>
            <th>Akcje</th>
        </tr>
        <?php if (empty($tasks)): ?>
            <tr>
                <td colspan="7">Brak zadań.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= htmlspecialchars($task['task']) ?></td>
                    <td><?= htmlspecialchars($task['category']) ?></td>
                    <td><?= htmlspecialchars($task['priority']) ?></td>
                    <td><?= $task['deadline'] ? date("d-m-Y H:i", strtotime($task['deadline'])) : "Brak terminu" ?></td>
                    <td>
                        <form method="POST" action="includes/update_status.php">
                            <input type="hidden" name="id" value="<?= $task['id'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="do zrobienia" <?= ($task['status'] == 'do zrobienia') ? 'selected' : '' ?>>❌ Do zrobienia</option>
                                <option value="w trakcie" <?= ($task['status'] == 'w trakcie') ? 'selected' : '' ?>>🕓 W trakcie</option>
                                <option value="zrobione" <?= ($task['status'] == 'zrobione') ? 'selected' : '' ?>>✅ Zrobione</option>
                            </select>
                        </form>
                    </td>
                    <td><?= htmlspecialchars($task['tag_list'] ?: "Brak tagów") ?></td>
                    <td>
                        <a href="includes/task_details.php?id=<?= $task['id'] ?>" class="btn">Szczegóły</a>
                        <?php if ($role == 'admin' || $task['user_id'] == $user_id): ?>
                            <a href="includes/edit_task.php?id=<?= $task['id'] ?>" class="btn">Edytuj</a>
                            <a href="includes/delete_task.php?id=<?= $task['id'] ?>" class="delete">Usuń</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

</div>

</body>
</html>
