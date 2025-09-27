<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Expense;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExpenseProvider implements ProviderInterface
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
            // Single expense
            $expense = $this->em->getRepository(Expense::class)->find($uriVariables['id']);
            if (!$expense || $expense->getUserId()->toString() !== $userId) {
                throw new NotFoundHttpException('Expense not found');
            }
            return $this->convertToResource($expense);
        }

        // Collection of expenses
        $qb = $this->em->getRepository(Expense::class)->createQueryBuilder('e');
        $qb->where('e.userId = :userId')
           ->setParameter('userId', $userId);

        // Apply filters
        $request = $context['request'] ?? null;
        if ($request) {
            if ($categoryId = $request->query->get('categoryId')) {
                $qb->andWhere('e.categoryId = :categoryId')
                   ->setParameter('categoryId', $categoryId);
            }

            if ($dateFrom = $request->query->get('dateFrom')) {
                $qb->andWhere('e.date >= :dateFrom')
                   ->setParameter('dateFrom', new \DateTime($dateFrom));
            }

            if ($dateTo = $request->query->get('dateTo')) {
                $qb->andWhere('e.date <= :dateTo')
                   ->setParameter('dateTo', new \DateTime($dateTo));
            }

            if ($minAmount = $request->query->get('minAmount')) {
                $qb->andWhere('e.amount >= :minAmount')
                   ->setParameter('minAmount', $minAmount);
            }

            if ($maxAmount = $request->query->get('maxAmount')) {
                $qb->andWhere('e.amount <= :maxAmount')
                   ->setParameter('maxAmount', $maxAmount);
            }
        }

        $qb->orderBy('e.occurredAt', 'DESC');
        $expenses = $qb->getQuery()->getResult();

        return array_map([$this, 'convertToResource'], $expenses);
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

