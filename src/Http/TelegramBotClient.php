<?php

namespace OlxMonitor\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class TelegramBotClient
{
    private const string API_URL = 'https://api.telegram.org/bot';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string          $botToken,
    ) {}

    /**
     * @throws \Exception
     */
    public function sendMessage(string $text, int $chatId): void
    {
        try {
            $this->httpClient->request('POST', self::API_URL . $this->botToken . '/sendMessage', [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ],
            ]);

            $this->logger->info('Telegram message sent successfully');
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to send Telegram message', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Telegram message sending failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function sendMediaGroup(string $message, array $photoUrls, int $chatId): void
    {
        try {
            $media = [];

            foreach (array_slice($photoUrls, 0, 10) as $index => $photoUrl) {
                $media[] = [
                    'type' => 'photo',
                    'media' => $photoUrl,
                    'caption' => $index === 0 ? $message : '',
                    'parse_mode' => 'HTML',
                ];
            }

            $this->httpClient->request('POST', self::API_URL . $this->botToken . '/sendMediaGroup', [
                'json' => [
                    'chat_id' => $chatId,
                    'media' => $media,
                ],
            ]);

            $this->logger->info('Telegram media group sent successfully');
        } catch (GuzzleException $e) {
            $this
                ->logger
                ->error('Failed to send Telegram media group', ['error' => $e->getMessage()]);

            throw new \RuntimeException('Telegram media group sending failed: ' . $e->getMessage(), 0, $e);
        }

        sleep(2);
    }

    public function sendPhoto(string $message, string $photoUrl, int $chatId): void
    {
        try {
            $this->httpClient->request('POST', self::API_URL . $this->botToken . '/sendPhoto', [
                'json' => [
                    'chat_id' => $chatId,
                    'photo' => $photoUrl,
                    'caption' => $message,
                    'parse_mode' => 'HTML',
                ],
            ]);

            $this->logger->info('Telegram photo sent successfully');
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to send Telegram photo', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Telegram photo sending failed: ' . $e->getMessage(), 0, $e);
        }

        sleep(2);
    }
}
