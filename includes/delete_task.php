<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("DELETE FROM comments WHERE task_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM task_logs WHERE task_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Błąd: " . $e->getMessage());
    }
}

header("Location: ../dashboard.php");
exit;
?>
