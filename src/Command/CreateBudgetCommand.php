<?php

namespace App\Command;

use App\Service\BudgetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

#[AsCommand(
    name: 'app:create-budget',
    description: 'Crea un presupuesto para una categoría',
)]
class CreateBudgetCommand extends Command
{
    public function __construct(
        private BudgetService $budgetService,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user_id', InputArgument::REQUIRED, 'UUID del usuario')
            ->addArgument('category_id', InputArgument::REQUIRED, 'UUID de la categoría')
            ->addArgument('amount', InputArgument::REQUIRED, 'Monto del presupuesto')
            ->addArgument('period_start', InputArgument::REQUIRED, 'Fecha de inicio (YYYY-MM-DD)')
            ->addArgument('period_end', InputArgument::REQUIRED, 'Fecha de fin (YYYY-MM-DD)')
            ->addArgument('currency', InputArgument::OPTIONAL, 'Moneda', 'USD');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $userId = Uuid::fromString($input->getArgument('user_id'));
            $categoryId = Uuid::fromString($input->getArgument('category_id'));
            $amount = (float) $input->getArgument('amount');
            $periodStart = new \DateTime($input->getArgument('period_start'));
            $periodEnd = new \DateTime($input->getArgument('period_end'));
            $currency = $input->getArgument('currency');

            // Verificar que la categoría existe y pertenece al usuario
            $category = $this->em->getRepository(\App\Entity\Category::class)->find($categoryId);
            if (!$category || $category->getUserId()->toString() !== $userId->toString()) {
                $io->error('La categoría no existe o no pertenece al usuario');
                return Command::FAILURE;
            }

            $budget = $this->budgetService->createBudget(
                $userId,
                $categoryId,
                $amount,
                $periodStart,
                $periodEnd,
                $currency
            );

            $status = $this->budgetService->getBudgetStatus($budget);

            $io->success('Presupuesto creado exitosamente!');
            $io->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $budget->getId()->toString()],
                    ['Categoría', $category->getName()],
                    ['Monto', $budget->getAmount() . ' ' . $budget->getCurrency()],
                    ['Período', $budget->getPeriodStart()->format('Y-m-d') . ' a ' . $budget->getPeriodEnd()->format('Y-m-d')],
                    ['Estado', $budget->isActive() ? 'Activo' : 'Inactivo'],
                ]
            );

            $io->section('Estado del Presupuesto:');
            $io->table(
                ['Métrica', 'Valor'],
                [
                    ['Gastado', $status['totalSpent'] . ' ' . $budget->getCurrency()],
                    ['Restante', $status['remaining'] . ' ' . $budget->getCurrency()],
                    ['Porcentaje usado', $status['percentageUsed'] . '%'],
                    ['Estado', $status['isOverBudget'] ? '⚠️ SOBRE PRESUPUESTO' : ($status['isNearLimit'] ? '⚠️ Cerca del límite' : '✅ OK')],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error al crear el presupuesto: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
