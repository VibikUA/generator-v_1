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

// Отримання записів історії для генератора
$stmt = $pdo->prepare("SELECT * FROM oil_changes_history WHERE generator_id = ? ORDER BY created_at DESC");
$stmt->execute([$generator_id]);
$generator_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Встановлення заголовків для завантаження CSV-файлу
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="history_' . $generator['name'] . '.csv"');

// Відкриваємо файл для запису
$output = fopen('php://output', 'w');

// Записуємо заголовки стовпців
fputcsv($output, ['Дата і час', 'Години']);

// Записуємо дані історії
foreach ($generator_history as $change) {
    fputcsv($output, [date('d.m.Y H:i', strtotime($change['date'])), $change['hours']]);
}

// Закриваємо файл
fclose($output);
exit;
?>
