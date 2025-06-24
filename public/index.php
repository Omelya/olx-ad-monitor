<?php

use OlxMonitor\DependencyInjection\Container;
use OlxMonitor\Repository\ListingRepositoryInterface;
use OlxMonitor\Repository\SearchFilterRepositoryInterface;
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
    'telegram_chat_id' => $_ENV['TELEGRAM_CHAT_ID'],
    'olx_api_url' => $_ENV['OLX_API_URL'],
    'log_level' => $_ENV['LOG_LEVEL'] ?? 'info',
];

$container = new Container($config);

$action = $_GET['action'] ?? 'dashboard';

try {
    match ($action) {
        'dashboard' => renderDashboard($container),
        'filters' => renderFilters($container),
        'listings' => renderListings($container),
        default => renderDashboard($container),
    };
} catch (\Exception $e) {
    echo "<h1>Error</h1><p>{$e->getMessage()}</p>";
}

function renderDashboard(Container $container): void
{
    /** @var ListingRepositoryInterface $listingRepo */
    $listingRepo = $container->get(ListingRepositoryInterface::class);

    /** @var SearchFilterRepositoryInterface $filterRepo */
    $filterRepo = $container->get(SearchFilterRepositoryInterface::class);

    $listings = $listingRepo->findAll();
    $filters = $filterRepo->findActive();

    $activeListings = array_filter($listings, static fn($l) => $l->isActive);

    echo renderTemplate('dashboard', [
        'total_listings' => count($listings),
        'active_listings' => count($activeListings),
        'total_filters' => count($filters),
        'recent_listings' => array_slice($listings, 0, 10),
    ]);
}

function renderFilters(Container $container): void
{
    /** @var SearchFilterRepositoryInterface $filterRepo */
    $filterRepo = $container->get(SearchFilterRepositoryInterface::class);

    $filters = $filterRepo->findActive();

    echo renderTemplate('filters', [
        'filters' => $filters,
    ]);
}

function renderListings(Container $container): void
{
    /** @var ListingRepositoryInterface $listingRepo */
    $listingRepo = $container->get(ListingRepositoryInterface::class);

    $listings = $listingRepo->findAll();

    echo renderTemplate('listings', [
        'listings' => $listings,
    ]);
}

function renderTemplate(string $template, array $data = []): string
{
    extract($data);

    return match ($template) {
        'dashboard' => "
            <!DOCTYPE html>
            <html lang='uk'>
            <head>
                <title>OLX Monitor Dashboard</title>
                <meta charset='utf-8'>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .stats { display: flex; gap: 20px; margin-bottom: 30px; }
                    .stat-card { background: #f5f5f5; padding: 20px; border-radius: 8px; text-align: center; }
                    .stat-number { font-size: 2em; font-weight: bold; color: #333; }
                    .stat-label { color: #666; margin-top: 5px; }
                    .listings { margin-top: 20px; }
                    .listing { background: white; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
                    .listing-title { font-weight: bold; margin-bottom: 5px; }
                    .listing-price { color: #e74c3c; font-weight: bold; }
                    .listing-location { color: #666; }
                    .nav { margin-bottom: 20px; }
                    .nav a { margin-right: 20px; text-decoration: none; color: #3498db; }
                </style>
            </head>
            <body>
                <h1>OLX Monitor Dashboard</h1>
                
                <div class='nav'>
                    <a href='?action=dashboard'>Dashboard</a>
                    <a href='?action=filters'>Filters</a>
                    <a href='?action=listings'>Listings</a>
                </div>
                
                <div class='stats'>
                    <div class='stat-card'>
                        <div class='stat-number'>{$total_listings}</div>
                        <div class='stat-label'>Total Listings</div>
                    </div>
                    <div class='stat-card'>
                        <div class='stat-number'>{$active_listings}</div>
                        <div class='stat-label'>Active Listings</div>
                    </div>
                    <div class='stat-card'>
                        <div class='stat-number'>{$total_filters}</div>
                        <div class='stat-label'>Active Filters</div>
                    </div>
                </div>
                
                <h2>Recent Listings</h2>
                <div class='listings'>
                    " . implode('', array_map(fn($listing) => "
                        <div class='listing'>
                            <div class='listing-title'>{$listing->title}</div>
                            <div class='listing-price'>{$listing->price} {$listing->currency}</div>
                            <div class='listing-location'>{$listing->location}</div>
                            <div><a href='{$listing->url}' target='_blank'>View on OLX</a></div>
                        </div>
                    ", $recent_listings)) . "
                </div>
            </body>
            </html>
        ",

        'filters' => "
            <!DOCTYPE html>
            <html lang='uk'>
            <head>
                <title>OLX Monitor - Filters</title>
                <meta charset='utf-8'>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .nav { margin-bottom: 20px; }
                    .nav a { margin-right: 20px; text-decoration: none; color: #3498db; }
                    .filter { background: white; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
                    .filter-name { font-weight: bold; margin-bottom: 5px; }
                    .filter-details { color: #666; }
                </style>
            </head>
            <body>
                <h1>Active Filters</h1>
                
                <div class='nav'>
                    <a href='?action=dashboard'>Dashboard</a>
                    <a href='?action=filters'>Filters</a>
                    <a href='?action=listings'>Listings</a>
                </div>
                
                <div class='filters'>
                    " . implode('', array_map(fn($filter) => "
                        <div class='filter'>
                            <div class='filter-name'>{$filter->name}</div>
                            <div class='filter-details'>
                                Category: {$filter->category} → {$filter->subcategory} → {$filter->type}<br>
                                Last checked: " . ($filter->lastChecked?->format('Y-m-d H:i:s') ?? 'Never') . "
                            </div>
                        </div>
                    ", $filters)) . "
                </div>
            </body>
            </html>
        ",

        'listings' => "
            <!DOCTYPE html>
            <html lang='uk'>
            <head>
                <title>OLX Monitor - Listings</title>
                <meta charset='utf-8'>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .nav { margin-bottom: 20px; }
                    .nav a { margin-right: 20px; text-decoration: none; color: #3498db; }
                    .listing { background: white; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
                    .listing-title { font-weight: bold; margin-bottom: 5px; }
                    .listing-price { color: #e74c3c; font-weight: bold; }
                    .listing-location { color: #666; }
                    .inactive { opacity: 0.5; }
                </style>
            </head>
            <body>
                <h1>All Listings</h1>
                
                <div class='nav'>
                    <a href='?action=dashboard'>Dashboard</a>
                    <a href='?action=filters'>Filters</a>
                    <a href='?action=listings'>Listings</a>
                </div>
                
                <div class='listings'>
                    " . implode('', array_map(fn($listing) => "
                        <div class='listing " . ($listing->isActive ? '' : 'inactive') . "'>
                            <div class='listing-title'>{$listing->title}</div>
                            <div class='listing-price'>{$listing->price} {$listing->currency}</div>
                            <div class='listing-location'>{$listing->location}</div>
                            <div>Status: " . ($listing->isActive ? 'Active' : 'Removed') . "</div>
                            <div><a href='{$listing->url}' target='_blank'>View on OLX</a></div>
                        </div>
                    ", $listings)) . "
                </div>
            </body>
            </html>
        ",
    };
}
