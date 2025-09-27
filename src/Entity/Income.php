<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'incomes')]
class Income
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['income:read'])]
    private UuidInterface $id;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['income:read'])]
    private UuidInterface $userId;

    #[ORM\Column(type: 'uuid')]
    #[Groups(['income:read', 'income:write'])]
    private UuidInterface $categoryId;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['income:read', 'income:write'])]
    private float $amount;

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['income:read', 'income:write'])]
    private string $currency;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['income:read', 'income:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['income:read', 'income:write'])]
    private \DateTime $date;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['income:read', 'income:write'])]
    private ?string $source = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['income:read', 'income:write'])]
    private ?array $tags = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['income:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['income:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = \Ramsey\Uuid\Uuid::uuid4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
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
}
