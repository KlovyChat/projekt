<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List - Strona Główna</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h2>Witaj w To-Do List!</h2>
    <p>Zarządzaj swoimi zadaniami w prosty i wygodny sposób.</p>

    <a href="auth/login.php" class="btn">Zaloguj się</a>
    <a href="auth/register.php" class="btn">Zarejestruj się</a>
</div>

</body>
</html>
