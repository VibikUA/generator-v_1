<?php
require 'db.php';  // Підключення до бази даних

// Функція для отримання всіх генераторів
function getGenerators($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM generators ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Додавання нового генератора
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_generator'])) {
    $name = $_POST['generator_name'];

    // Функція для додавання генератора
    function addGenerator($pdo, $name) {
        $stmt = $pdo->prepare("INSERT INTO generators (name, status) VALUES (?, 0)");
        $stmt->execute([$name]);
    }

    addGenerator($pdo, $name);

    header('Location: add_generator.php');
    exit;
}

// Редагування назви генератора
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_generator'])) {
    $id = $_POST['generator_id'];
    $name = $_POST['generator_name'];

    $stmt = $pdo->prepare("UPDATE generators SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    header('Location: add_generator.php');
    exit;
}

// Зміна статусу генератора (увімкнення/вимкнення)
if (isset($_GET['toggle_generator'])) {
    $id = $_GET['toggle_generator'];

    // Спочатку отримуємо поточний статус генератора
    $stmt = $pdo->prepare("SELECT status FROM generators WHERE id = ?");
    $stmt->execute([$id]);
    $generator = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($generator) {
        // Змінюємо статус на протилежний
        $newStatus = $generator['status'] == 1 ? 0 : 1;

        // Оновлюємо статус генератора
        $stmt = $pdo->prepare("UPDATE generators SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
    }

    header('Location: add_generator.php');
    exit;
}

// Видалення генератора
if (isset($_GET['delete_generator'])) {
    $id = $_GET['delete_generator'];
    $stmt = $pdo->prepare("DELETE FROM generators WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: add_generator.php');
    exit;
}

// Отримання всіх генераторів
$generators = getGenerators($pdo);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Додавання нового генератора та управління</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Додати новий генератор</h1>
        
        <!-- Форма для додавання генератора -->
        <form action="add_generator.php" method="post">
            <label for="generator_name">Назва нового генератора:</label>
            <input type="text" id="generator_name" name="generator_name" required>
            <button type="submit" name="add_generator">Додати генератор</button>
        </form>

        <h2>Перелік генераторів</h2>
        <table>
            <thead>
                <tr>
                    <th>Назва</th>
                    <th>Статус</th>
                    <th>Дата створення</th>
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($generators as $generator): ?>
                    <tr>
                        <td><?= htmlspecialchars($generator['name']) ?></td>
                        <td>
                            <?= $generator['status'] ? 'Увімкнено' : 'Вимкнено' ?>
                            (<a href="add_generator.php?toggle_generator=<?= $generator['id'] ?>">
                                <?= $generator['status'] ? 'Вимкнути' : 'Увімкнути' ?>
                            </a>)
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($generator['created_at'])) ?></td>
                        <td>
                            <a href="add_generator.php?edit_generator=<?= $generator['id'] ?>">Редагувати</a> |
                            <a href="add_generator.php?delete_generator=<?= $generator['id'] ?>" onclick="return confirm('Ви впевнені, що хочете видалити цей генератор?');">Видалити</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Форма для редагування генератора -->
        <?php if (isset($_GET['edit_generator'])): ?>
            <?php
            $id = $_GET['edit_generator'];
            $stmt = $pdo->prepare("SELECT * FROM generators WHERE id = ?");
            $stmt->execute([$id]);
            $generator = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <?php if ($generator): ?>
                <h2>Редагувати генератор "<?= htmlspecialchars($generator['name']) ?>"</h2>
                <form action="add_generator.php" method="post">
                    <input type="hidden" name="generator_id" value="<?= $generator['id'] ?>">
                    <label for="generator_name">Нова назва генератора:</label>
                    <input type="text" id="generator_name" name="generator_name" value="<?= htmlspecialchars($generator['name']) ?>" required>
                    <button type="submit" name="edit_generator">Оновити назву</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <a href="index.php" class="back-button">Повернутися на головну</a>
    </div>
</body>
</html>
