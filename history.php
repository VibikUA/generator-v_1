<?php
require 'db.php';  // Підключення до бази даних

// Перевірка, чи передано ID генератора
if (!isset($_GET['generator_id'])) {
    die('Ідентифікатор генератора не вказано.');
}

$generator_id = $_GET['generator_id'];

// Отримання інформації про генератор
$stmt = $pdo->prepare("SELECT * FROM generators WHERE id = ?");
$stmt->execute([$generator_id]);
$generator = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$generator) {
    die('Генератор не знайдено.');
}

// Функція для отримання записів історії для генератора
function getGeneratorHistory($pdo, $generator_id) {
    $stmt = $pdo->prepare("SELECT * FROM oil_changes_history WHERE generator_id = ? ORDER BY created_at DESC");
    $stmt->execute([$generator_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Видалення запису з історії
if (isset($_GET['delete_record'])) {
    $record_id = $_GET['delete_record'];
    $stmt = $pdo->prepare("DELETE FROM oil_changes_history WHERE id = ?");
    $stmt->execute([$record_id]);

    header('Location: history.php?generator_id=' . $generator_id);
    exit;
}

$generator_history = getGeneratorHistory($pdo, $generator_id);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Історія замін масла для генератора "<?= htmlspecialchars($generator['name']) ?>"</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Історія замін масла для генератора "<?= htmlspecialchars($generator['name']) ?>"</h1>

        <a href="export_to_csv.php?generator_id=<?= $generator_id ?>" class="history-button">Експортувати в Excel</a>

        <table>
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Години</th>
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($generator_history as $change): ?>
                    <tr>
                        <td><?= date('d.m.Y H:i', strtotime($change['date'])) ?></td>
                        <td><?= htmlspecialchars($change['hours']) ?></td>
                        <td>
                            <a href="history.php?generator_id=<?= $generator_id ?>&delete_record=<?= $change['id'] ?>" onclick="return confirm('Ви впевнені, що хочете видалити цей запис?');">Видалити</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="index.php" class="back-button">Повернутися на головну</a>
    </div>
</body>
</html>
