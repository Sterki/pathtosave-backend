<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Budget;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Ramsey\Uuid\Uuid;

class BudgetProcessor implements ProcessorInterface
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
        
        if (str_contains($operation->getName(), '_delete')) {
            // The provider already loaded and validated the budget
            // $data should be the BudgetResource from the provider
        
            if (!isset($data->id) || $data->id === '') {
                throw new BadRequestHttpException('Invalid budget data provided for delete operation');
            }

            // Find the actual Budget entity using the ID from the resource
            $budget = $this->em->getRepository(Budget::class)->find($data->id);
            if (!$budget || $budget->getUserId()->toString() !== $userId) {
                throw new NotFoundHttpException('Budget not found');
            }
            
            // Delete the budget using EntityManager
            $this->em->remove($budget);
            $this->em->flush();

            // Return the deleted budget data for confirmation
            return $data;
        }

        if (str_contains($operation->getName(), '_patch') || str_contains($operation->getName(), '_put')) {
            // Update existing budget (PATCH/PUT)
            if (!isset($uriVariables['id'])) {
                throw new BadRequestHttpException('Budget ID is required for update operations');
            }

            $budget = $this->em->getRepository(Budget::class)->find($uriVariables['id']);
            if (!$budget || $budget->getUserId()->toString() !== $userId) {
                throw new NotFoundHttpException('Budget not found');
            }

            // Update fields if provided
            if (isset($data->categoryId)) {
                try {
                    $categoryId = Uuid::fromString($data->categoryId);
                } catch (\InvalidArgumentException $e) {
                    throw new BadRequestHttpException('Invalid category ID format: ' . $data->categoryId);
                }
                
                $category = $this->em->getRepository(Category::class)->find($categoryId);
                if (!$category) {
                    throw new BadRequestHttpException('Category not found with ID: ' . $data->categoryId);
                }
                if ($category->getUserId()->toString() !== $userId) {
                    throw new BadRequestHttpException('Category does not belong to user');
                }
                $budget->setCategoryId(Uuid::fromString($data->categoryId));
            }

            if (isset($data->amount)) $budget->setAmount($data->amount);
            if (isset($data->currency)) $budget->setCurrency($data->currency);
            if (isset($data->periodStart)) $budget->setPeriodStart(new \DateTime($data->periodStart));
            if (isset($data->periodEnd)) $budget->setPeriodEnd(new \DateTime($data->periodEnd));
            if (isset($data->isActive)) $budget->setIsActive($data->isActive);
            if (isset($data->name)) $budget->setName($data->name);
            if (isset($data->period)) $budget->setPeriod($data->period);

            $budget->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();

            return $this->convertToResource($budget);
        }

        if ($operation->getName() === 'post' || !isset($uriVariables['id'])) {
            // Create new budget
            $budget = new Budget();
            $budget->setUserId(Uuid::fromString($userId));
            
            // Validate category exists and belongs to user
            if (!isset($data->categoryId) || $data->categoryId === null || $data->categoryId === '') {
                throw new BadRequestHttpException('Category ID is required. Debug info: ' . json_encode($debugInfo));
            }
            
            try {
                $categoryId = Uuid::fromString($data->categoryId);
            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException('Invalid category ID format: "' . $data->categoryId . '". Debug info: ' . json_encode($debugInfo));
            }
            
            $category = $this->em->getRepository(Category::class)->find($categoryId);
            if (!$category) {
                throw new BadRequestHttpException('Category not found with ID: ' . $data->categoryId);
            }
            
            if ($category->getUserId()->toString() !== $userId) {
                throw new BadRequestHttpException('Category does not belong to user');
            }
            
            $budget->setCategoryId(Uuid::fromString($data->categoryId));
            $budget->setAmount($data->amount);
            $budget->setCurrency($data->currency ?? 'USD');
            $budget->setPeriodStart(new \DateTime($data->periodStart));
            $budget->setPeriodEnd(new \DateTime($data->periodEnd));
            $budget->setIsActive($data->isActive ?? true);
            $budget->setName($data->name ?? null);
            $budget->setPeriod($data->period ?? null);

            $this->em->persist($budget);
            $this->em->flush();

            return $this->convertToResource($budget);
        }

        // Update existing budget (PUT/PATCH)
        if (!isset($uriVariables['id'])) {
            throw new BadRequestHttpException('Budget ID is required for update operations');
        }

        $budget = $this->em->getRepository(Budget::class)->find($uriVariables['id']);
        if (!$budget || $budget->getUserId()->toString() !== $userId) {
            throw new NotFoundHttpException('Budget not found');
        }

        if (isset($data->categoryId)) {
            try {
                $categoryId = Uuid::fromString($data->categoryId);
            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException('Invalid category ID format: ' . $data->categoryId);
            }
            
            $category = $this->em->getRepository(Category::class)->find($categoryId);
            if (!$category) {
                throw new BadRequestHttpException('Category not found with ID: ' . $data->categoryId);
            }
            if ($category->getUserId()->toString() !== $userId) {
                throw new BadRequestHttpException('Category does not belong to user');
            }
            $budget->setCategoryId(Uuid::fromString($data->categoryId));
        }

        if (isset($data->amount)) $budget->setAmount($data->amount);
        if (isset($data->currency)) $budget->setCurrency($data->currency);
        if (isset($data->periodStart)) $budget->setPeriodStart(new \DateTime($data->periodStart));
        if (isset($data->periodEnd)) $budget->setPeriodEnd(new \DateTime($data->periodEnd));
        if (isset($data->isActive)) $budget->setIsActive($data->isActive);
        if (isset($data->name)) $budget->setName($data->name);
        if (isset($data->period)) $budget->setPeriod($data->period);

        $budget->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->convertToResource($budget);
    }

    private function convertToResource(Budget $budget): object
    {
        $resource = new \App\ApiResource\BudgetResource();
        $resource->id = $budget->getId()->toString();
        $resource->userId = $budget->getUserId()->toString();
        $resource->categoryId = $budget->getCategoryId()->toString();
        $resource->amount = $budget->getAmount();
        $resource->currency = $budget->getCurrency();
        $resource->periodStart = $budget->getPeriodStart()->format('Y-m-d');
        $resource->periodEnd = $budget->getPeriodEnd()->format('Y-m-d');
        $resource->isActive = $budget->isActive();
        $resource->createdAt = $budget->getCreatedAt()->format('Y-m-d H:i:s');
        $resource->updatedAt = $budget->getUpdatedAt()->format('Y-m-d H:i:s');
        $resource->name = $budget->getName() ?? '';
        $resource->period = $budget->getPeriod() ?? '';

        return $resource;
    }
}

