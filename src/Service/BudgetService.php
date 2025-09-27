<?php

namespace App\Service;

use App\Entity\Budget;
use App\Entity\Expense;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class BudgetService
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Crear un presupuesto para una categoría y período específico
     */
    public function createBudget(
        UuidInterface $userId,
        UuidInterface $categoryId,
        float $amount,
        \DateTime $periodStart,
        \DateTime $periodEnd,
        string $currency = 'USD'
    ): Budget {
        // Verificar que la categoría existe y pertenece al usuario
        $category = $this->em->getRepository(Category::class)->find($categoryId);
        if (!$category || $category->getUserId()->toString() !== $userId->toString()) {
            throw new \InvalidArgumentException('Invalid category');
        }

        // Verificar que no hay solapamiento de presupuestos activos
        $existingBudget = $this->findActiveBudget($userId, $categoryId, $periodStart, $periodEnd);
        if ($existingBudget) {
            throw new \InvalidArgumentException('Ya existe un presupuesto activo para esta categoría en este período');
        }

        $budget = new Budget();
        $budget->setUserId($userId);
        $budget->setCategoryId($categoryId);
        $budget->setAmount($amount);
        $budget->setCurrency($currency);
        $budget->setPeriodStart($periodStart);
        $budget->setPeriodEnd($periodEnd);
        $budget->setIsActive(true);

        $this->em->persist($budget);
        $this->em->flush();

        $this->logger->info('Budget created', [
            'userId' => $userId->toString(),
            'categoryId' => $categoryId->toString(),
            'amount' => $amount,
            'period' => $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d')
        ]);

        return $budget;
    }

    /**
     * Calcular el total gastado en una categoría dentro de un período
     */
    public function getTotalSpentInPeriod(
        UuidInterface $userId,
        UuidInterface $categoryId,
        \DateTime $periodStart,
        \DateTime $periodEnd
    ): float {
        $qb = $this->em->getRepository(Expense::class)->createQueryBuilder('e');
        $qb->select('SUM(e.amount)')
           ->where('e.userId = :userId')
           ->andWhere('e.categoryId = :categoryId')
           ->andWhere('e.occurredAt >= :periodStart')
           ->andWhere('e.occurredAt <= :periodEnd')
           ->setParameter('userId', $userId->toString())
           ->setParameter('categoryId', $categoryId->toString())
           ->setParameter('periodStart', $periodStart)
           ->setParameter('periodEnd', $periodEnd);

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result ? (float) $result : 0.0;
    }

    /**
     * Obtener el estado del presupuesto (porcentaje usado, restante, etc.)
     */
    public function getBudgetStatus(Budget $budget): array
    {
        $totalSpent = $this->getTotalSpentInPeriod(
            $budget->getUserId(),
            $budget->getCategoryId(),
            $budget->getPeriodStart(),
            $budget->getPeriodEnd()
        );

        $budgetAmount = $budget->getAmount();
        $remaining = $budgetAmount - $totalSpent;
        $percentageUsed = $budgetAmount > 0 ? ($totalSpent / $budgetAmount) * 100 : 0;

        return [
            'budgetAmount' => $budgetAmount,
            'totalSpent' => $totalSpent,
            'remaining' => $remaining,
            'percentageUsed' => round($percentageUsed, 2),
            'isOverBudget' => $totalSpent > $budgetAmount,
            'isNearLimit' => $percentageUsed >= 80, // 80% usado
            'periodStart' => $budget->getPeriodStart()->format('Y-m-d'),
            'periodEnd' => $budget->getPeriodEnd()->format('Y-m-d'),
        ];
    }

    /**
     * Verificar si un gasto excedería el presupuesto
     */
    public function checkBudgetBeforeExpense(
        UuidInterface $userId,
        UuidInterface $categoryId,
        float $expenseAmount,
        \DateTime $expenseDate
    ): array {
        // Buscar presupuestos activos que cubran la fecha del gasto
        $qb = $this->em->getRepository(Budget::class)->createQueryBuilder('b');
        $qb->where('b.userId = :userId')
           ->andWhere('b.categoryId = :categoryId')
           ->andWhere('b.isActive = true')
           ->andWhere('b.periodStart <= :expenseDate')
           ->andWhere('b.periodEnd >= :expenseDate')
           ->setParameter('userId', $userId->toString())
           ->setParameter('categoryId', $categoryId->toString())
           ->setParameter('expenseDate', $expenseDate);

        $activeBudgets = $qb->getQuery()->getResult();
        
        if (empty($activeBudgets)) {
            return [
                'hasBudget' => false,
                'message' => 'No hay presupuesto definido para esta categoría en esta fecha',
                'canProceed' => true
            ];
        }

        $budget = $activeBudgets[0]; // Tomar el primero si hay múltiples
        $status = $this->getBudgetStatus($budget);
        $newTotal = $status['totalSpent'] + $expenseAmount;

        return [
            'hasBudget' => true,
            'budgetAmount' => $status['budgetAmount'],
            'currentSpent' => $status['totalSpent'],
            'newTotal' => $newTotal,
            'remaining' => $status['remaining'],
            'willExceedBudget' => $newTotal > $status['budgetAmount'],
            'willBeNearLimit' => ($newTotal / $status['budgetAmount']) * 100 >= 80,
            'canProceed' => true, // Permitir el gasto pero con advertencias
            'warnings' => $this->generateWarnings($status, $newTotal)
        ];
    }

    /**
     * Generar advertencias basadas en el estado del presupuesto
     */
    private function generateWarnings(array $status, float $newTotal): array
    {
        $warnings = [];
        
        if ($newTotal > $status['budgetAmount']) {
            $warnings[] = '⚠️ Este gasto excederá tu presupuesto';
        } elseif (($newTotal / $status['budgetAmount']) * 100 >= 90) {
            $warnings[] = '⚠️ Te acercas al límite de tu presupuesto (90%+)';
        } elseif (($newTotal / $status['budgetAmount']) * 100 >= 80) {
            $warnings[] = '⚠️ Has usado más del 80% de tu presupuesto';
        }

        return $warnings;
    }

    /**
     * Buscar presupuesto activo que cubra un período específico
     */
    private function findActiveBudget(
        UuidInterface $userId,
        UuidInterface $categoryId,
        \DateTime $periodStart,
        \DateTime $periodEnd
    ): ?Budget {
        $qb = $this->em->getRepository(Budget::class)->createQueryBuilder('b');
        $qb->where('b.userId = :userId')
           ->andWhere('b.categoryId = :categoryId')
           ->andWhere('b.isActive = true')
           ->andWhere('(b.periodStart <= :periodStart AND b.periodEnd >= :periodStart) OR (b.periodStart <= :periodEnd AND b.periodEnd >= :periodEnd) OR (b.periodStart >= :periodStart AND b.periodEnd <= :periodEnd)')
           ->setParameter('userId', $userId->toString())
           ->setParameter('categoryId', $categoryId->toString())
           ->setParameter('periodStart', $periodStart)
           ->setParameter('periodEnd', $periodEnd);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Obtener todos los presupuestos activos de un usuario con su estado
     */
    public function getUserBudgetsWithStatus(UuidInterface $userId): array
    {
        $qb = $this->em->getRepository(Budget::class)->createQueryBuilder('b');
        $qb->where('b.userId = :userId')
           ->andWhere('b.isActive = true')
           ->orderBy('b.periodStart', 'DESC')
           ->setParameter('userId', $userId->toString());

        $budgets = $qb->getQuery()->getResult();
        $result = [];

        foreach ($budgets as $budget) {
            $status = $this->getBudgetStatus($budget);
            $result[] = [
                'budget' => $budget,
                'status' => $status
            ];
        }

        return $result;
    }
}
