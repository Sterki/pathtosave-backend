<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Budget;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BudgetProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $userId = $user->getUserIdentifier();

        if ($operation->getName() === 'get') {
            // Single budget
            $budget = $this->em->getRepository(Budget::class)->find($uriVariables['id']);
            if (!$budget || $budget->getUserId()->toString() !== $userId) {
                throw new NotFoundHttpException('Budget not found');
            }
            return $this->convertToResource($budget);
        }

        // Collection of budgets
        $qb = $this->em->getRepository(Budget::class)->createQueryBuilder('b');
        $qb->where('b.userId = :userId')
           ->setParameter('userId', $userId);

        // Apply filters
        $request = $context['request'] ?? null;
        if ($request) {
            if ($categoryId = $request->query->get('categoryId')) {
                $qb->andWhere('b.categoryId = :categoryId')
                   ->setParameter('categoryId', $categoryId);
            }

            if ($isActive = $request->query->get('isActive')) {
                $qb->andWhere('b.isActive = :isActive')
                   ->setParameter('isActive', $isActive === 'true');
            }

            if ($periodStart = $request->query->get('periodStart')) {
                $qb->andWhere('b.periodStart >= :periodStart')
                   ->setParameter('periodStart', new \DateTime($periodStart));
            }

            if ($periodEnd = $request->query->get('periodEnd')) {
                $qb->andWhere('b.periodEnd <= :periodEnd')
                   ->setParameter('periodEnd', new \DateTime($periodEnd));
            }
        }

        $qb->orderBy('b.periodStart', 'DESC');
        $budgets = $qb->getQuery()->getResult();

        return array_map([$this, 'convertToResource'], $budgets);
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

        return $resource;
    }
}

