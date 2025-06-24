<?php

namespace OlxMonitor\Service;

use OlxMonitor\Entity\Listing;
use OlxMonitor\Entity\UserFilter;
use OlxMonitor\Http\TelegramBotClient;
use Psr\Log\LoggerInterface;

readonly class NotificationService
{
    public function __construct(
        private TelegramBotClient $telegramClient,
        private LoggerInterface   $logger,
    ) {}

    /**
     * @param UserFilter[] $users
     */
    public function notifyNewListing(Listing $listing, array $users): void
    {
        $message = "🆕 Нове оголошення!\n\n";
        $message .= "📋 {$listing->title}\n";
        $message .= "💰 {$listing->price} {$listing->currency}\n";
        $message .= "📍 {$listing->location}\n";
        $message .= "🔗 {$listing->url}";

        foreach ($users as $user) {
            $this->sendNotification($message, $listing->images, $user->chatId);
        }
    }

    /**
     * @param UserFilter[] $users
     */
    public function notifyPriceChange(Listing $oldListing, Listing $newListing, array $users): void
    {
        $priceChange = $newListing->price - $oldListing->price;
        $changeEmoji = $priceChange > 0 ? '📈' : '📉';

        $message = "{$changeEmoji} Зміна ціни!\n\n";
        $message .= "📋 {$newListing->title}\n";
        $message .= "💰 Було: {$oldListing->price} {$oldListing->currency}\n";
        $message .= "💰 Стало: {$newListing->price} {$newListing->currency}\n";
        $message .= "📊 Різниця: " . ($priceChange > 0 ? '+' : '') . "{$priceChange} {$newListing->currency}\n";
        $message .= "🔗 {$newListing->url}";

        foreach ($users as $user) {
            $this->sendNotification($message, $oldListing->images, $user->chatId);
        }
    }

    /**
     * @param UserFilter[] $users
     */
    public function notifyListingRemoved(Listing $listing, array $users): void
    {
        $message = "❌ Оголошення видалено\n\n";
        $message .= "📋 {$listing->title}\n";
        $message .= "💰 {$listing->price} {$listing->currency}\n";
        $message .= "📍 {$listing->location}";

        foreach ($users as $user) {
            $this->sendNotification($message, $listing->images, $user->chatId);
        }
    }

    private function sendNotification(string $message, array $photoUrls, int $chatId): void
    {
        try {
            if (count($photoUrls) > 1) {
                $this->telegramClient->sendMediaGroup($message, $photoUrls, $chatId);
            } else {
                $this->telegramClient->sendPhoto($message, $photoUrls[0], $chatId);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send Telegram notification', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }
}
