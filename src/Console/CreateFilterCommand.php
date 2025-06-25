<?php

namespace OlxMonitor\Console;

use OlxMonitor\Entity\SearchFilter;
use OlxMonitor\Repository\SearchFilterRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'filter:create',
    description: 'Create a new search filter'
)]
class CreateFilterCommand extends Command
{
    public function __construct(
        private readonly SearchFilterRepositoryInterface $filterRepository,
        private readonly LoggerInterface                 $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Filter name')
            ->addOption('category', null, InputOption::VALUE_REQUIRED, 'Category (e.g., нерухомість)')
            ->addOption('subcategory', null, InputOption::VALUE_REQUIRED, 'Subcategory (e.g., квартири)')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type (e.g., довгострокова оренда)')
            ->addOption('price', null, InputOption::VALUE_OPTIONAL, 'Price (e.g., 10000 or 10000,15000)')
            ->addOption('region-id', null, InputOption::VALUE_OPTIONAL, 'Region ID')
            ->addOption('city-id', null, InputOption::VALUE_OPTIONAL, 'City ID')
            ->addOption('apartment-type', null, InputOption::VALUE_OPTIONAL, 'Apartment Type')
            ->addOption('area', null, InputOption::VALUE_OPTIONAL, 'Area (e.g., 10 or 10,20)')
            ->addOption('distance', null, InputOption::VALUE_OPTIONAL, 'Distance in km (e.g., 10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $filters = [];

            if ($input->getOption('price')) {
                $filters['price'] = $input->getOption('price');
            }

            if ($input->getOption('region-id')) {
                $filters['region_id'] = (int) $input->getOption('region-id');
            }

            if ($input->getOption('city-id')) {
                $filters['city_id'] = (int) $input->getOption('city-id');
            }

            if ($input->getOption('apartment-type')) {
                $filters['apartment_type'] = (int) $input->getOption('apartment-type');
            }

            if ($input->getOption('area')) {
                $filters['area'] = $input->getOption('area');
            }

            if ($input->getOption('distance')) {
                $filters['distance'] = $input->getOption('distance');
            }

            $filter = new SearchFilter(
                id: Uuid::uuid4(),
                name: $input->getOption('name'),
                category: $input->getOption('category'),
                subcategory: $input->getOption('subcategory'),
                type: $input->getOption('type'),
                filters: $filters,
            );

            $this->filterRepository->save($filter);

            $io->success('Filter created successfully!');
            $io->table(['Property', 'Value'], [
                ['ID', $filter->id->toString()],
                ['Name', $filter->name],
                ['Category', $filter->category],
                ['Subcategory', $filter->subcategory],
                ['Type', $filter->type],
                ['Filters', json_encode($filter->filters, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Create filter command failed', [
                'error' => $e->getMessage(),
            ]);

            $io->error('Failed to create filter: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
