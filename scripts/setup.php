<?php

use OlxMonitor\DependencyInjection\Container;
use Doctrine\DBAL\Connection;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$config = [
    'database_user' => $_ENV['DATABASE_USER'],
    'database_password' => $_ENV['DATABASE_PASSWORD'],
    'database_name' => $_ENV['DATABASE_NAME'],
    'database_host' => $_ENV['DATABASE_HOST'],
    'telegram_bot_token' => $_ENV['TELEGRAM_BOT_TOKEN'],
    'olx_api_url' => $_ENV['OLX_API_URL'],
    'log_level' => $_ENV['LOG_LEVEL'] ?? 'info',
];

$container = new Container($config);

/** @var Connection $connection */
$connection = $container->get(Connection::class);

echo "Setting up database...\n";

$migrationSql = file_get_contents(__DIR__ . '/../migrations/001_create_initial_schema.sql');
$statements = array_filter(array_map('trim', explode(';', $migrationSql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        $connection->executeStatement($statement);
    }
}

echo "Database setup completed!\n";
echo "You can now create filters using: php bin/console filter:create\n";
echo "And start monitoring with: php bin/console monitor:run\n";
echo "Or run the daemon: php bin/monitor-daemon\n";
