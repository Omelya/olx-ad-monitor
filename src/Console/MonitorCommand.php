<?php

declare(strict_types=1);

namespace OlxMonitor\Console;

use OlxMonitor\Service\MonitoringService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'monitor:run',
    description: 'Run OLX listings monitoring for all active filters'
)]
class MonitorCommand extends Command
{
    public function __construct(
        private readonly MonitoringService $monitoringService,
        private readonly LoggerInterface   $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'filter-id',
            'f',
            InputOption::VALUE_OPTIONAL,
            'Monitor specific filter by ID',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $filterId = $input->getOption('filter-id');

            if ($filterId) {
                $io->info("Monitoring specific filter: {$filterId}");
                // Monitoring specific filter logic would go here
            } else {
                $io->info('Starting monitoring for all active filters...');
                $this->monitoringService->monitorAllFilters();
                $io->success('Monitoring completed successfully!');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Monitoring command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('Monitoring failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
