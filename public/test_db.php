<?php
include '/var/www/html/db.php';
$query = $pdo->query('SELECT 1');
echo 'Database connection successful!';

$stmt = $pdo->query('SELECT * FROM articles');
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<pre>';
print_r($articles);
echo '</pre>';
?>