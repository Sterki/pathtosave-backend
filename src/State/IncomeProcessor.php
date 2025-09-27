<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Income;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Ramsey\Uuid\Uuid;

class IncomeProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $userId = $user->getUserIdentifier();

        if ($operation->getName() === 'post') {
            // Create new income
            $income = new Income();
            $income->setUserId(Uuid::fromString($userId));
            
            // Validate category exists and belongs to user
            $category = $this->em->getRepository(Category::class)->find($data->categoryId);
            if (!$category || $category->getUserId()->toString() !== $userId) {
                throw new BadRequestHttpException('Invalid category');
            }
            
            $income->setCategoryId(Uuid::fromString($data->categoryId));
            $income->setAmount($data->amount);
            $income->setCurrency($data->currency ?? 'USD');
            $income->setDescription($data->description);
            $income->setDate(new \DateTime($data->date));
            $income->setSource($data->source);
            $income->setTags($data->tags);

            $this->em->persist($income);
            $this->em->flush();

            return $this->convertToResource($income);
        }

        // Update existing income
        $income = $this->em->getRepository(Income::class)->find($uriVariables['id']);
        if (!$income || $income->getUserId()->toString() !== $userId) {
            throw new NotFoundHttpException('Income not found');
        }

        if (isset($data->categoryId)) {
            $category = $this->em->getRepository(Category::class)->find($data->categoryId);
            if (!$category || $category->getUserId()->toString() !== $userId) {
                throw new BadRequestHttpException('Invalid category');
            }
            $income->setCategoryId(Uuid::fromString($data->categoryId));
        }

        if (isset($data->amount)) $income->setAmount($data->amount);
        if (isset($data->currency)) $income->setCurrency($data->currency);
        if (isset($data->description)) $income->setDescription($data->description);
        if (isset($data->date)) $income->setDate(new \DateTime($data->date));
        if (isset($data->source)) $income->setSource($data->source);
        if (isset($data->tags)) $income->setTags($data->tags);

        $income->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->convertToResource($income);
    }

    private function convertToResource(Income $income): object
    {
        $resource = new \App\ApiResource\IncomeResource();
        $resource->id = $income->getId()->toString();
        $resource->userId = $income->getUserId()->toString();
        $resource->categoryId = $income->getCategoryId()->toString();
        $resource->amount = $income->getAmount();
        $resource->currency = $income->getCurrency();
        $resource->description = $income->getDescription();
        $resource->date = $income->getDate()->format('Y-m-d');
        $resource->source = $income->getSource();
        $resource->tags = $income->getTags();
        $resource->createdAt = $income->getCreatedAt()->format('Y-m-d H:i:s');
        $resource->updatedAt = $income->getUpdatedAt()->format('Y-m-d H:i:s');

        return $resource;
    }
}

