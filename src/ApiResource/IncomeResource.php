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
use App\Entity\Income;
use App\State\IncomeProcessor;
use App\State\IncomeProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            provider: IncomeProvider::class,
            parameters: [
                new QueryParameter('categoryId', description: 'Filter by category ID', required: false),
                new QueryParameter('dateFrom', description: 'Filter incomes from date (YYYY-MM-DD)', required: false),
                new QueryParameter('dateTo', description: 'Filter incomes to date (YYYY-MM-DD)', required: false),
                new QueryParameter('minAmount', description: 'Minimum amount filter', required: false),
                new QueryParameter('maxAmount', description: 'Maximum amount filter', required: false),
            ]
        ),
        new Post(processor: IncomeProcessor::class),
        new Get(provider: IncomeProvider::class),
        new Put(processor: IncomeProcessor::class),
        new Patch(processor: IncomeProcessor::class),
        new Delete(processor: IncomeProcessor::class),
    ],
    normalizationContext: ['groups' => ['income:read']],
    denormalizationContext: ['groups' => ['income:write']]
)]
class IncomeResource
{
    public string $id;
    public string $userId;
    public string $categoryId;
    public float $amount;
    public string $currency;
    public ?string $description = null;
    public string $date;
    public ?string $source = null;
    public ?array $tags = null;
    public string $createdAt;
    public string $updatedAt;
}
