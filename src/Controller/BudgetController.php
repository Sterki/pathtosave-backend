<?php

namespace App\Controller;

use App\Service\BudgetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Ramsey\Uuid\Uuid;

#[Route('/api/v1/budgets', name: 'api_budgets_')]
class BudgetController extends AbstractController
{
    public function __construct(
        private BudgetService $budgetService,
        private Security $security
    ) {}

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function getBudgetStatus(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $userId = Uuid::fromString($user->getUserIdentifier());
        $budgetsWithStatus = $this->budgetService->getUserBudgetsWithStatus($userId);

        $response = [
            'budgets' => [],
            'summary' => [
                'totalBudgets' => count($budgetsWithStatus),
                'overBudget' => 0,
                'nearLimit' => 0,
                'totalBudgetAmount' => 0,
                'totalSpent' => 0
            ]
        ];

        foreach ($budgetsWithStatus as $budgetData) {
            $budget = $budgetData['budget'];
            $status = $budgetData['status'];

            $response['budgets'][] = [
                'id' => $budget->getId()->toString(),
                'categoryId' => $budget->getCategoryId()->toString(),
                'amount' => $budget->getAmount(),
                'currency' => $budget->getCurrency(),
                'periodStart' => $budget->getPeriodStart()->format('Y-m-d'),
                'periodEnd' => $budget->getPeriodEnd()->format('Y-m-d'),
                'status' => $status
            ];

            // Actualizar resumen
            $response['summary']['totalBudgetAmount'] += $status['budgetAmount'];
            $response['summary']['totalSpent'] += $status['totalSpent'];
            
            if ($status['isOverBudget']) {
                $response['summary']['overBudget']++;
            }
            
            if ($status['isNearLimit']) {
                $response['summary']['nearLimit']++;
            }
        }

        $response['summary']['totalRemaining'] = $response['summary']['totalBudgetAmount'] - $response['summary']['totalSpent'];

        return new JsonResponse($response);
    }

    #[Route('/check-expense', name: 'check_expense', methods: ['POST'])]
    public function checkExpenseAgainstBudget(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['categoryId']) || !isset($data['amount']) || !isset($data['date'])) {
            return new JsonResponse(['error' => 'Missing required fields: categoryId, amount, date'], 400);
        }

        $userId = Uuid::fromString($user->getUserIdentifier());
        $categoryId = Uuid::fromString($data['categoryId']);
        $amount = (float) $data['amount'];
        $date = new \DateTime($data['date']);

        $result = $this->budgetService->checkBudgetBeforeExpense($userId, $categoryId, $amount, $date);

        return new JsonResponse($result);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createBudget(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        $requiredFields = ['categoryId', 'amount', 'periodStart', 'periodEnd'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(['error' => "Missing required field: $field"], 400);
            }
        }

        try {
            $userId = Uuid::fromString($user->getUserIdentifier());
            $categoryId = Uuid::fromString($data['categoryId']);
            $amount = (float) $data['amount'];
            $periodStart = new \DateTime($data['periodStart']);
            $periodEnd = new \DateTime($data['periodEnd']);
            $currency = $data['currency'] ?? 'USD';

            $budget = $this->budgetService->createBudget(
                $userId,
                $categoryId,
                $amount,
                $periodStart,
                $periodEnd,
                $currency
            );

            $status = $this->budgetService->getBudgetStatus($budget);

            return new JsonResponse([
                'success' => true,
                'budget' => [
                    'id' => $budget->getId()->toString(),
                    'categoryId' => $budget->getCategoryId()->toString(),
                    'amount' => $budget->getAmount(),
                    'currency' => $budget->getCurrency(),
                    'periodStart' => $budget->getPeriodStart()->format('Y-m-d'),
                    'periodEnd' => $budget->getPeriodEnd()->format('Y-m-d'),
                    'isActive' => $budget->isActive()
                ],
                'status' => $status
            ], 201);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}

