<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use App\Entity\Profile;
use App\State\ProfileProcessor;
use App\State\ProfileProvider;

#[ApiResource(
    operations: [
        new GetCollection(provider: ProfileProvider::class),
        new Get(provider: ProfileProvider::class),
        new Put(processor: ProfileProcessor::class),
        new Patch(processor: ProfileProcessor::class),
    ],
    normalizationContext: ['groups' => ['profile:read']],
    denormalizationContext: ['groups' => ['profile:write']]
)]
class ProfileResource
{
    public string $userId;
    public string $email;
    public ?string $displayName = null;
    public string $currency;
    public string $timezone;
    public string $createdAt;
    public string $updatedAt;
}

