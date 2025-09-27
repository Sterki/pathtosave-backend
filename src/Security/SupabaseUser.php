<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class SupabaseUser implements UserInterface
{
    private string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function getUserIdentifier(): string
    {
        return $this->userId;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // No credentials to erase for JWT
    }
}
