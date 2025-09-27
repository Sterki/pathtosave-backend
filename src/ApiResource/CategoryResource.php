<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use App\Entity\Category;
use App\State\CategoryProcessor;
use App\State\CategoryProvider;

#[ApiResource(
    operations: [
        new GetCollection(provider: CategoryProvider::class),
        new Post(processor: CategoryProcessor::class),
        new Get(provider: CategoryProvider::class),
        new Put(processor: CategoryProcessor::class),
        new Patch(processor: CategoryProcessor::class),
        new Delete(processor: CategoryProcessor::class),
    ],
    normalizationContext: ['groups' => ['category:read']],
    denormalizationContext: ['groups' => ['category:write']]
)]
class CategoryResource
{
    public string $id;
    public string $name;
    public string $type;
    public ?string $color = null;
    public ?string $icon = null;
    public bool $isArchived = false;
    public int $sortOrder = 0;
}
