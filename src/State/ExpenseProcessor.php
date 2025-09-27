<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Expense;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Ramsey\Uuid\Uuid;

class ExpenseProcessor implements ProcessorInterface
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
            // Create new expense
            $expense = new Expense();
            $expense->setUserId(Uuid::fromString($userId));
            
            // Validate category exists and belongs to user
            $category = $this->em->getRepository(Category::class)->find($data->categoryId);
            if (!$category || $category->getUserId()->toString() !== $userId) {
                throw new BadRequestHttpException('Invalid category');
            }
            
            $expense->setCategoryId(Uuid::fromString($data->categoryId));
            $expense->setAmount($data->amount);
            $expense->setCurrency($data->currency ?? 'USD');
            $expense->setDescription($data->description);
            $expense->setDate(new \DateTime($data->date));
            $expense->setLocation($data->location);
            $expense->setTags($data->tags);

            $this->em->persist($expense);
            $this->em->flush();

            return $this->convertToResource($expense);
        }

        // Update existing expense
        $expense = $this->em->getRepository(Expense::class)->find($uriVariables['id']);
        if (!$expense || $expense->getUserId()->toString() !== $userId) {
            throw new NotFoundHttpException('Expense not found');
        }

        if (isset($data->categoryId)) {
            $category = $this->em->getRepository(Category::class)->find($data->categoryId);
            if (!$category || $category->getUserId()->toString() !== $userId) {
                throw new BadRequestHttpException('Invalid category');
            }
            $expense->setCategoryId(Uuid::fromString($data->categoryId));
        }

        if (isset($data->amount)) $expense->setAmount($data->amount);
        if (isset($data->currency)) $expense->setCurrency($data->currency);
        if (isset($data->description)) $expense->setDescription($data->description);
        if (isset($data->date)) $expense->setDate(new \DateTime($data->date));
        if (isset($data->location)) $expense->setLocation($data->location);
        if (isset($data->tags)) $expense->setTags($data->tags);

        $expense->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->convertToResource($expense);
    }

    private function convertToResource(Expense $expense): object
    {
        $resource = new \App\ApiResource\ExpenseResource();
        $resource->id = $expense->getId()->toString();
        $resource->userId = $expense->getUserId()->toString();
        $resource->categoryId = $expense->getCategoryId()->toString();
        $resource->amount = $expense->getAmount();
        $resource->currency = $expense->getCurrency();
        $resource->description = $expense->getDescription();
        $resource->date = $expense->getDate()->format('Y-m-d');
        $resource->location = $expense->getLocation();
        $resource->tags = $expense->getTags();
        $resource->createdAt = $expense->getCreatedAt()->format('Y-m-d H:i:s');
        $resource->updatedAt = $expense->getUpdatedAt()->format('Y-m-d H:i:s');

        return $resource;
    }
}

