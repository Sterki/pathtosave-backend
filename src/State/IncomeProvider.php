<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Income;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IncomeProvider implements ProviderInterface
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
            // Single income
            $income = $this->em->getRepository(Income::class)->find($uriVariables['id']);
            if (!$income || $income->getUserId()->toString() !== $userId) {
                throw new NotFoundHttpException('Income not found');
            }
            return $this->convertToResource($income);
        }

        // Collection of incomes
        $qb = $this->em->getRepository(Income::class)->createQueryBuilder('i');
        $qb->where('i.userId = :userId')
           ->setParameter('userId', $userId);

        // Apply filters
        $request = $context['request'] ?? null;
        if ($request) {
            if ($categoryId = $request->query->get('categoryId')) {
                $qb->andWhere('i.categoryId = :categoryId')
                   ->setParameter('categoryId', $categoryId);
            }

            if ($dateFrom = $request->query->get('dateFrom')) {
                $qb->andWhere('i.date >= :dateFrom')
                   ->setParameter('dateFrom', new \DateTime($dateFrom));
            }

            if ($dateTo = $request->query->get('dateTo')) {
                $qb->andWhere('i.date <= :dateTo')
                   ->setParameter('dateTo', new \DateTime($dateTo));
            }

            if ($minAmount = $request->query->get('minAmount')) {
                $qb->andWhere('i.amount >= :minAmount')
                   ->setParameter('minAmount', $minAmount);
            }

            if ($maxAmount = $request->query->get('maxAmount')) {
                $qb->andWhere('i.amount <= :maxAmount')
                   ->setParameter('maxAmount', $maxAmount);
            }
        }

        $qb->orderBy('i.occurredAt', 'DESC');
        $incomes = $qb->getQuery()->getResult();

        return array_map([$this, 'convertToResource'], $incomes);
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

