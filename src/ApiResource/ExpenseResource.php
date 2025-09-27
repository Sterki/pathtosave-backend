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
use App\Entity\Expense;
use App\State\ExpenseProcessor;
use App\State\ExpenseProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            provider: ExpenseProvider::class,
            parameters: [
                new QueryParameter('categoryId', description: 'Filter by category ID', required: false),
                new QueryParameter('dateFrom', description: 'Filter expenses from date (YYYY-MM-DD)', required: false),
                new QueryParameter('dateTo', description: 'Filter expenses to date (YYYY-MM-DD)', required: false),
                new QueryParameter('minAmount', description: 'Minimum amount filter', required: false),
                new QueryParameter('maxAmount', description: 'Maximum amount filter', required: false),
            ]
        ),
        new Post(processor: ExpenseProcessor::class),
        new Get(provider: ExpenseProvider::class),
        new Put(processor: ExpenseProcessor::class),
        new Patch(processor: ExpenseProcessor::class),
        new Delete(processor: ExpenseProcessor::class),
    ],
    normalizationContext: ['groups' => ['expense:read']],
    denormalizationContext: ['groups' => ['expense:write']]
)]
class ExpenseResource
{
    public string $id;
    public string $userId;
    public string $categoryId;
    public float $amount;
    public string $currency;
    public ?string $description = null;
    public string $date;
    public ?string $location = null;
    public ?array $tags = null;
    public string $createdAt;
    public string $updatedAt;
}
