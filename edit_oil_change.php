<?php
require 'db.php';  // Підключення до бази даних

// Отримання запису для редагування
if (!isset($_GET['id'])) {
    die('Не вказано ID запису для редагування.');
}

$oil_change_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM oil_changes WHERE id = ?");
$stmt->execute([$oil_change_id]);
$oil_change = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oil_change) {
    die('Запис не знайдено.');
}

// Оновлення запису
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_oil_change'])) {
    $hours = $_POST['hours'];
    $date = $_POST['date'];

    $stmt = $pdo->prepare("UPDATE oil_changes SET hours = ?, date = ? WHERE id = ?");
    $stmt->execute([$hours, $date, $oil_change_id]);

    header('Location: index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагування запису використання генератора</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Редагувати запис використання генератора</h1>
        
        <form action="edit_oil_change.php?id=<?= $oil_change_id ?>" method="post">
            <label for="date">Дата і час:</label>
            <input type="datetime-local" id="date" name="date" value="<?= date('Y-m-d\TH:i', strtotime($oil_change['date'])) ?>" required>

            <label for="hours">Години:</label>
            <input type="number" id="hours" name="hours" value="<?= htmlspecialchars($oil_change['hours']) ?>" required>

            <button type="submit" name="update_oil_change">Оновити запис</button>
        </form>

        <a href="index.php" class="back-button">Повернутися на головну</a>
    </div>
</body>
</html>
