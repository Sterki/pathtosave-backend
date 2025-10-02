<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\QueryParameter;
use App\Entity\Budget;
use App\State\BudgetProcessor;
use App\State\BudgetProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            provider: BudgetProvider::class,
            parameters: [
                new QueryParameter('categoryId', description: 'Filter by category ID', required: false),
                new QueryParameter('isActive', description: 'Filter by active status', required: false),
                new QueryParameter('periodStart', description: 'Filter by period start date (YYYY-MM-DD)', required: false),
                new QueryParameter('periodEnd', description: 'Filter by period end date (YYYY-MM-DD)', required: false),
            ]
        ),
        new Post(processor: BudgetProcessor::class),
        new Get(provider: BudgetProvider::class),
        new Put(processor: BudgetProcessor::class),
        new Patch(
            processor: BudgetProcessor::class, 
            provider: BudgetProvider::class,
            formats: ['json' => ['application/json']]
        ),
        new Delete(provider: BudgetProvider::class),
    ]
)]
class BudgetResource
{
    public string $id = '';
    public string $userId = '';
    public ?string $categoryId = null;
    public float $amount = 0.0;
    public string $currency = 'USD';
    public string $periodStart = '';
    public string $periodEnd = '';
    public bool $isActive = true;
    public string $createdAt = '';
    public string $updatedAt = '';
    public string $name = '';
    public string $period = '';
}