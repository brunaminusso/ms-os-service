<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

$driver = getenv('DB_DRIVER') ?: 'pdo_mysql';
if ($driver === 'pdo_mysql') {
    $driver = 'mysql';
}
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'os_service';
$user = getenv('DB_USER') ?: 'os_user';
$password = getenv('DB_PASSWORD') ?: 'os_pass';

$dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=utf8mb4', $driver, $host, $port, $dbName);
$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$sql = file_get_contents(__DIR__ . '/../database/seeds.sql');
if ($sql === false) {
    throw new RuntimeException('Seed file not found.');
}

$statements = preg_split('/;\s*(\r?\n|$)/', $sql);
foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '' || str_starts_with($statement, '/*') || str_starts_with($statement, '--')) {
        continue;
    }
    try {
        $pdo->exec($statement);
    } catch (PDOException $exception) {
        // Allow rerunning seed data without failing on duplicate keys.
        if ($exception->getCode() !== '23000') {
            throw $exception;
        }
    }
}

echo "Seed completed.\n";
