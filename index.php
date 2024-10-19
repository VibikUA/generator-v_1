<?php
require 'db.php';  // Підключення до бази даних

date_default_timezone_set('Europe/Kiev');  // Встановлення часового поясу для України

// Функція для отримання тільки увімкнених генераторів
function getActiveGenerators($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM generators WHERE status = 1 ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Функція для додавання годин використання генератора
function addOilChange($pdo, $generator_id, $date, $hours) {
    $stmt = $pdo->prepare("INSERT INTO oil_changes (generator_id, date, hours) VALUES (?, ?, ?)");
    $stmt->execute([$generator_id, $date, $hours]);
}

// Функція для видалення запису використання генератора
function deleteOilChange($pdo, $oil_change_id) {
    $stmt = $pdo->prepare("DELETE FROM oil_changes WHERE id = ?");
    $stmt->execute([$oil_change_id]);
}

// Функція для підрахунку загальних годин генератора
function getTotalHours($pdo, $generator_id) {
    $stmt = $pdo->prepare("SELECT SUM(hours) as total_hours FROM oil_changes WHERE generator_id = ?");
    $stmt->execute([$generator_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_hours'] ?? 0;
}

// Функція для отримання всіх записів використання генератора
function getOilChanges($pdo, $generator_id) {
    $stmt = $pdo->prepare("SELECT * FROM oil_changes WHERE generator_id = ? ORDER BY date DESC");
    $stmt->execute([$generator_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Функція для переміщення даних в історію замін масла
function moveToHistory($pdo, $generator_id) {
    // Витягуємо всі записи для генератора
    $stmt = $pdo->prepare("SELECT * FROM oil_changes WHERE generator_id = ?");
    $stmt->execute([$generator_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Додаємо ці записи до таблиці історії
    foreach ($records as $record) {
        $stmt = $pdo->prepare("INSERT INTO oil_changes_history (generator_id, date, hours) VALUES (?, ?, ?)");
        $stmt->execute([$record['generator_id'], $record['date'], $record['hours']]);
    }

    // Видаляємо записи з основної таблиці
    $stmt = $pdo->prepare("DELETE FROM oil_changes WHERE generator_id = ?");
    $stmt->execute([$generator_id]);
}

// Додавання запису про використання генератора
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_oil_change'])) {
    $generator_id = $_POST['generator_id'];
    $hours = $_POST['hours'];
    $date = date('Y-m-d H:i:s');  // Автоматична дата і час в українському форматі

    addOilChange($pdo, $generator_id, $date, $hours);

    header('Location: index.php');
    exit;
}

// Видалення запису про використання генератора
if (isset($_GET['delete_oil_change'])) {
    $oil_change_id = $_GET['delete_oil_change'];
    deleteOilChange($pdo, $oil_change_id);

    header('Location: index.php');
    exit;
}

// Переміщення даних в історію після натискання кнопки "Заміна масла"
if (isset($_POST['replace_oil'])) {
    $generator_id = $_POST['generator_id'];
    moveToHistory($pdo, $generator_id);

    header('Location: index.php');
    exit;
}

// Отримання всіх увімкнених генераторів
$generators = getActiveGenerators($pdo);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Відстеження заміни масла генераторів</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Відстеження заміни масла генераторів</h1>

        <!-- Відображення загальних годин і введення годин для кожного генератора окремо -->
        <?php foreach ($generators as $generator): ?>
            <?php 
                $total_hours = getTotalHours($pdo, $generator['id']); 
                $oil_changes = getOilChanges($pdo, $generator['id']);
            ?>
            <div class="generator-box">
                <h3>Генератор: <?= htmlspecialchars($generator['name']) ?></h3>
                <p>Загальна кількість годин: <?= $total_hours ?></p>

                <?php if ($total_hours >= 90): ?>
                    <strong style="color: red;">ЗАМІНА МАСЛА!</strong>
                    <form action="index.php" method="post">
                        <input type="hidden" name="generator_id" value="<?= $generator['id'] ?>">
                        <button type="submit" name="replace_oil">Підтвердити заміну масла</button>
                    </form>
                <?php else: ?>
                    <p>Залишилось годин до заміни: <?= 90 - $total_hours ?></p>
                <?php endif; ?>

                <!-- Форма для додавання годин використання генератора -->
                <h4>Додавання годин роботи для генератора "<?= htmlspecialchars($generator['name']) ?>"</h4>
                <form action="index.php" method="post">
                    <input type="hidden" name="generator_id" value="<?= $generator['id'] ?>">
                    <label for="hours_<?= $generator['id'] ?>">Години відпрацьовано:</label>
                    <input type="number" id="hours_<?= $generator['id'] ?>" name="hours" required>
                    <button type="submit" name="add_oil_change">Додати запис</button>
                </form>

                <!-- Таблиця записів використання генератора -->
                <h4>Записи використання генератора "<?= htmlspecialchars($generator['name']) ?>"</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Дата і час запису</th>
                            <th>Години</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($oil_changes as $change): ?>
                            <tr>
                                <td><?= date('d.m.Y H:i', strtotime($change['date'])) ?></td>
                                <td><?= htmlspecialchars($change['hours']) ?></td>
                                <td>
                                    <a href="edit_oil_change.php?id=<?= $change['id'] ?>">Редагувати</a> |
                                    <a href="index.php?delete_oil_change=<?= $change['id'] ?>" onclick="return confirm('Ви впевнені, що хочете видалити цей запис?');">Видалити</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <!-- Посилання на сторінки історії замін масла -->
        <h2>Історія замін масла</h2>
        <div class="history-buttons">
            <?php foreach ($generators as $generator): ?>
                <a href="history.php?generator_id=<?= $generator['id'] ?>" class="history-button">Історія генератора "<?= htmlspecialchars($generator['name']) ?>"</a>
            <?php endforeach; ?>
        </div>
        <link id="themeStylesheet" rel="stylesheet" href="style.css"> <!-- Початковий стиль, змінюється через JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Встановити вибраний стиль зі збережених налаштувань (якщо є)
            let savedTheme = localStorage.getItem('selectedTheme');
            if (savedTheme) {
                document.getElementById('themeStylesheet').setAttribute('href', savedTheme);
            }

            // Встановлення вибору в випадаючому меню, якщо тема вже збережена
            if (savedTheme) {
                document.getElementById('themeSelector').value = savedTheme;
            }

            // Обробник для зміни теми
            document.getElementById('themeSelector').addEventListener('change', function () {
                let selectedTheme = this.value;
                document.getElementById('themeStylesheet').setAttribute('href', selectedTheme);
                localStorage.setItem('selectedTheme', selectedTheme); // Зберегти вибір в LocalStorage
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <!-- Випадаюче меню для вибору стилю -->
        <label for="themeSelector">Оберіть стиль сайту:</label>
        <select id="themeSelector">
            <option value="style.css">Зелений</option>
            <option value="style1.css">Сучасний (синьо-зелений)</option>
            <option value="style2.css">Темний стиль</option>
            <option value="style3.css">Мінімалістичний</option>
            <option value="style4.css">Пастельний стиль у рожево-лілових тонах</option>
            <option value="style5.css">Теплий стиль у коричнево-оранжевих тонах</option>
            <option value="style6.css">Індустріальний стиль у сірому та синьому</option>
            
        </select>
         
    </div>
        <a href="add_generator.php" class="back-button">Додати або переглянути генератори</a>
    </div>
</body>
</html>
