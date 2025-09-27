<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WebhookDto
{
    #[Assert\NotBlank]
    public string $type;

    #[Assert\NotBlank]
    public string $table;

    #[Assert\NotBlank]
    public array $record;

    #[Assert\NotBlank]
    public array $schema;

    #[Assert\NotBlank]
    public array $old_record;

    public function getUserId(): ?string
    {
        return $this->record['id'] ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->record['email'] ?? null;
    }

    public function getUserMetadata(): ?array
    {
        return $this->record['user_metadata'] ?? null;
    }

    public function isUserCreated(): bool
    {
        return $this->type === 'INSERT' && $this->table === 'auth.users';
    }
}

