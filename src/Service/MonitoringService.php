<?php

namespace OlxMonitor\Service;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use OlxMonitor\Entity\Listing;
use OlxMonitor\Entity\SearchFilter;
use OlxMonitor\Exception\OlxApiException;
use OlxMonitor\Http\OlxApiClient;
use OlxMonitor\Repository\DoctrineUserFiltersRepository;
use OlxMonitor\Repository\ListingRepositoryInterface;
use OlxMonitor\Repository\SearchFilterRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

readonly class MonitoringService
{
    public function __construct(
        private OlxApiClient                    $olxApiClient,
        private ListingRepositoryInterface      $listingRepository,
        private SearchFilterRepositoryInterface $filterRepository,
        private DoctrineUserFiltersRepository   $userFiltersRepository,
        private NotificationService             $notificationService,
        private PriceHistoryService             $priceHistoryService,
        private LoggerInterface                 $logger,
    ) {}

    public function monitorAllFilters(): void
    {
        $activeFilters = $this->filterRepository->findActive();

        foreach ($activeFilters as $filter) {
            try {
                $this->monitorFilter($filter);
            } catch (\Exception $e) {
                $this->logger->error('Failed to monitor filter', [
                    'filter_id' => $filter->id->toString(),
                    'filter_name' => $filter->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @throws OlxApiException
     * @throws \JsonException
     * @throws \Exception
     * @throws Exception
     */
    public function monitorFilter(SearchFilter $filter): void
    {
        $this->logger->info('Starting filter monitoring', [
            'filter_id' => $filter->id->toString(),
            'filter_name' => $filter->name,
        ]);

        $currentListings = $this->olxApiClient->searchListings($filter);
        $existingListings = $this->listingRepository->findActiveByFilterId($filter->id->toString());
        $users = $this->userFiltersRepository->findAllByFilterId($filter->id->toString());

        $existingMap = [];

        foreach ($existingListings as $listing) {
            $existingMap[$listing->externalId] = $listing;
        }

        $currentIds = [];

        foreach ($currentListings as $listingData) {
            $currentIds[] = $listingData['id'];
            $existingListing = $existingMap[$listingData['id']] ?? null;

            if ($existingListing === null) {
                $newListing = $this->createListingFromData($listingData, $filter);

                $this->listingRepository->save($newListing);
                $this->notificationService->notifyNewListing($newListing, $users);

                $this->logger->info('New listing found', [
                    'id' => $newListing->externalId,
                    'title' => $newListing->title,
                ]);
            } else if (abs($existingListing->price - $listingData['price']) > 0.01) {
                $updatedListing = $existingListing->withPrice($listingData['price']);
                $this->listingRepository->save($updatedListing);

                $this->priceHistoryService->recordPriceChange($existingListing, $updatedListing);
                $this->notificationService->notifyPriceChange($existingListing, $updatedListing, $users);

                $this->logger->info('Price change detected', [
                    'external_id' => $existingListing->externalId,
                    'old_price' => $existingListing->price,
                    'new_price' => $listingData['price'],
                ]);
            }
        }

        foreach ($existingListings as $existingListing) {
            if (!in_array($existingListing->externalId, $currentIds, true)) {
                $inactiveListing = $existingListing->markAsInactive();
                $this->listingRepository->save($inactiveListing);
                $this->notificationService->notifyListingRemoved($existingListing, $users);

                $this->logger->info('Listing removed', [
                    'external_id' => $existingListing->externalId,
                    'title' => $existingListing->title,
                ]);
            }
        }

        $updatedFilter = $filter->withLastChecked(new DateTimeImmutable());
        $this->filterRepository->save($updatedFilter);

        $this->logger->info('Filter monitoring completed', [
            'filter_id' => $filter->id->toString(),
            'current_listings_count' => count($currentListings),
            'existing_listings_count' => count($existingListings),
        ]);
    }

    /**
     * @throws \Exception
     */
    private function createListingFromData(array $data, SearchFilter $filter): Listing
    {
        return new Listing(
            id: Uuid::uuid4(),
            externalId: $data['id'],
            filterId: $filter->id->toString(),
            title: $data['title'],
            description: $data['description'],
            price: $data['price'],
            currency: $data['currency'],
            url: $data['url'],
            location: $data['location'],
            images: $data['images'],
            publishedAt: new DateTimeImmutable($data['refreshed_at']),
            createdAt: new DateTimeImmutable(),
        );
    }
}
