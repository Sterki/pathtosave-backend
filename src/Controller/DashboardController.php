<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(private Connection $connection) {}

    #[Route('/summary', methods: ['GET'])]
    public function summary(Request $request): JsonResponse
    {
        $from = $request->query->get('from', date('Y-m-01'));
        $to = $request->query->get('to', date('Y-m-t'));

        $result = $this->connection->executeQuery(
            'SELECT * FROM public.summary(?, ?)',
            [$from, $to]
        )->fetchAssociative();

        return $this->json($result);
    }

    #[Route('/daily-spend', methods: ['GET'])]
    public function dailySpend(Request $request): JsonResponse
    {
        $month = $request->query->get('month', date('Y-m-01'));

        $results = $this->connection->executeQuery(
            'SELECT * FROM public.daily_spend(?)',
            [$month]
        )->fetchAllAssociative();

        return $this->json(['days' => $results]);
    }

    #[Route('/expenses/by-category', methods: ['GET'])]
    public function expensesByCategory(Request $request): JsonResponse
    {
        $from = $request->query->get('from', date('Y-m-01'));
        $to = $request->query->get('to', date('Y-m-t'));

        $results = $this->connection->executeQuery(
            'SELECT * FROM public.expenses_by_category(?, ?)',
            [$from, $to]
        )->fetchAllAssociative();

        return $this->json($results);
    }
}
