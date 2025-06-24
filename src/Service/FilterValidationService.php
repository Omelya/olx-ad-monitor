<?php

namespace OlxMonitor\Service;

use OlxMonitor\Entity\SearchFilter;
use OlxMonitor\Exception\ValidationException;

class FilterValidationService
{
    private const array VALID_CATEGORIES = [
        'нерухомість' => [
            'квартири' => ['довгострокова оренда', 'подобова оренда', 'продаж'],
            'будинки' => ['продаж', 'оренда'],
            'комерційна нерухомість' => ['продаж', 'оренда'],
            'земельні ділянки' => ['продаж'],
        ],
        'транспорт' => [
            'легкові автомобілі' => [''],
            'мотоцикли' => [''],
            'автобуси' => [''],
            'вантажівки' => [''],
        ],
        'робота' => [
            'вакансії' => [''],
            'резюме' => [''],
        ],
    ];

    /**
     * @throws ValidationException
     */
    public function validateFilter(SearchFilter $filter): void
    {
        $this->validateCategory($filter->category, $filter->subcategory, $filter->type);
        $this->validateFilters($filter->filters);
        $this->validateName($filter->name);
    }

    /**
     * @throws ValidationException
     */
    private function validateCategory(string $category, string $subcategory, string $type): void
    {
        if (!isset(self::VALID_CATEGORIES[$category])) {
            throw new ValidationException("Invalid category: {$category}");
        }

        if (!isset(self::VALID_CATEGORIES[$category][$subcategory])) {
            throw new ValidationException("Invalid subcategory: {$subcategory} for category: {$category}");
        }

        $validTypes = self::VALID_CATEGORIES[$category][$subcategory];
        if (!empty($validTypes) && !in_array($type, $validTypes, true)) {
            throw new ValidationException("Invalid type: {$type} for subcategory: {$subcategory}");
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateFilters(array $filters): void
    {
        if (isset($filters['price_from'], $filters['price_to'])) {
            if ($filters['price_from'] >= $filters['price_to']) {
                throw new ValidationException('price_from must be less than price_to');
            }
        }

        if (isset($filters['price_from']) && $filters['price_from'] < 0) {
            throw new ValidationException('price_from must be positive');
        }

        if (isset($filters['price_to']) && $filters['price_to'] < 0) {
            throw new ValidationException('price_to must be positive');
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new ValidationException('Filter name cannot be empty');
        }

        if (strlen($name) > 255) {
            throw new ValidationException('Filter name cannot exceed 255 characters');
        }
    }
}
