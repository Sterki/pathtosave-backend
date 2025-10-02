<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'budgets')]
class Budget
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['budget:read'])]
    private UuidInterface $id;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['budget:read'])]
    private UuidInterface $userId;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['budget:read', 'budget:write'])]
    private UuidInterface $categoryId;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['budget:read', 'budget:write'])]
    private float $amount;

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['budget:read', 'budget:write'])]
    private string $currency;

    #[ORM\Column(type: 'date')]
    #[Groups(['budget:read', 'budget:write'])]
    private \DateTime $periodStart;

    #[ORM\Column(type: 'date')]
    #[Groups(['budget:read', 'budget:write'])]
    private \DateTime $periodEnd;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['budget:read', 'budget:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['budget:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['budget:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string')]
    #[Groups(['budget:read', 'budget:write'])]
    private string $name;

    #[ORM\Column(type: 'string')]
    #[Groups(['budget:read', 'budget:write'])]
    private string $period;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->period = 'custom';
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

    public function setUserId(UuidInterface $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCategoryId(): UuidInterface
    {
        return $this->categoryId;
    }

    public function setCategoryId(UuidInterface $categoryId): self
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getPeriodStart(): \DateTime
    {
        return $this->periodStart;
    }

    public function setPeriodStart(\DateTime $periodStart): self
    {
        $this->periodStart = $periodStart;
        return $this;
    }

    public function getPeriodEnd(): \DateTime
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(\DateTime $periodEnd): self
    {
        $this->periodEnd = $periodEnd;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPeriod(): string
    {
        return $this->period;
    }

    public function setPeriod(string $period): self
    {
        $this->period = $period;
        return $this;
    }
}
