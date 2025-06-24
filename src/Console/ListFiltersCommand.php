<?php

namespace OlxMonitor\Console;

use OlxMonitor\Repository\SearchFilterRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'filter:list',
    description: 'List all search filters'
)]
class ListFiltersCommand extends Command
{
    public function __construct(
        private readonly SearchFilterRepositoryInterface $filterRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filters = $this->filterRepository->findActive();

        if (empty($filters)) {
            $io->info('No active filters found.');
            return Command::SUCCESS;
        }

        $rows = [];

        foreach ($filters as $filter) {
            $rows[] = [
                $filter->id->toString(),
                $filter->name,
                $filter->category,
                $filter->subcategory,
                $filter->type,
                $filter->lastChecked?->format('Y-m-d H:i:s') ?? 'Never',
                $filter->isActive ? 'Yes' : 'No',
            ];
        }

        $io->table(
            ['ID', 'Name', 'Category', 'Subcategory', 'Type', 'Last Checked', 'Active'],
            $rows,
        );

        return Command::SUCCESS;
    }
}
