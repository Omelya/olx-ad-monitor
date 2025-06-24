<?php

namespace OlxMonitor\DependencyInjection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OlxMonitor\Console\CreateFilterCommand;
use OlxMonitor\Console\ListFiltersCommand;
use OlxMonitor\Console\MonitorCommand;
use OlxMonitor\Http\OlxApiClient;
use OlxMonitor\Http\TelegramBotClient;
use OlxMonitor\Repository\DoctrineListingRepository;
use OlxMonitor\Repository\DoctrinePriceHistoryRepository;
use OlxMonitor\Repository\DoctrineSearchFilterRepository;
use OlxMonitor\Repository\DoctrineUserFiltersRepository;
use OlxMonitor\Repository\ListingRepositoryInterface;
use OlxMonitor\Repository\PriceHistoryRepositoryInterface;
use OlxMonitor\Repository\SearchFilterRepositoryInterface;
use OlxMonitor\Repository\UserFiltersRepositoryInterface;
use OlxMonitor\Service\MonitoringService;
use OlxMonitor\Service\NotificationService;
use OlxMonitor\Service\PriceHistoryService;
use Psr\Log\LoggerInterface;

class Container
{
    private array $services = [];

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $id): mixed
    {
        if (!isset($this->services[$id])) {
            $this->services[$id] = match ($id) {
                'config' => $this->config,

                LoggerInterface::class => $this->createLogger(),
                ClientInterface::class => new Client(['timeout' => 30]),
                Connection::class => $this->createDatabaseConnection(),

                ListingRepositoryInterface::class => new DoctrineListingRepository(
                    $this->get(Connection::class),
                ),

                SearchFilterRepositoryInterface::class => new DoctrineSearchFilterRepository(
                    $this->get(Connection::class),
                ),

                PriceHistoryRepositoryInterface::class => new DoctrinePriceHistoryRepository(
                    $this->get(Connection::class),
                ),

                UserFiltersRepositoryInterface::class => new DoctrineUserFiltersRepository(
                    $this->get(Connection::class),
                ),

                PriceHistoryService::class => new PriceHistoryService(
                    $this->get(PriceHistoryRepositoryInterface::class),
                ),

                OlxApiClient::class => new OlxApiClient(
                    $this->get(ClientInterface::class),
                    $this->get(LoggerInterface::class),
                    $this->config['olx_api_url'],
                ),

                TelegramBotClient::class => new TelegramBotClient(
                    $this->get(ClientInterface::class),
                    $this->get(LoggerInterface::class),
                    $this->config['telegram_bot_token'],
                ),

                NotificationService::class => new NotificationService(
                    $this->get(TelegramBotClient::class),
                    $this->get(LoggerInterface::class),
                ),

                MonitoringService::class => new MonitoringService(
                    $this->get(OlxApiClient::class),
                    $this->get(ListingRepositoryInterface::class),
                    $this->get(SearchFilterRepositoryInterface::class),
                    $this->get(UserFiltersRepositoryInterface::class),
                    $this->get(NotificationService::class),
                    $this->get(PriceHistoryService::class),
                    $this->get(LoggerInterface::class),
                ),

                MonitorCommand::class => new MonitorCommand(
                    $this->get(MonitoringService::class),
                    $this->get(LoggerInterface::class),
                ),

                CreateFilterCommand::class => new CreateFilterCommand(
                    $this->get(SearchFilterRepositoryInterface::class),
                    $this->get(LoggerInterface::class),
                ),

                ListFiltersCommand::class => new ListFiltersCommand(
                    $this->get(SearchFilterRepositoryInterface::class),
                ),

                default => throw new \InvalidArgumentException("Service '{$id}' not found"),
            };
        }

        return $this->services[$id];
    }

    private function createLogger(): LoggerInterface
    {
        $logger = new Logger('olx-monitor');
        $logger->pushHandler(new StreamHandler('php://stdout', $this->config['log_level'] ?? 'info'));
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', $this->config['log_level'] ?? 'info'));

        return $logger;
    }

    private function createDatabaseConnection(): Connection
    {
        return DriverManager::getConnection([
            'dbname'   => $this->config['database_name'],
            'user'     => $this->config['database_user'],
            'password' => $this->config['database_password'],
            'host'     => $this->config['database_host'],
            'driver' => 'pdo_mysql',
        ]);
    }
}
