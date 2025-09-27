<?php

namespace App\Command;

use App\Entity\Expense;
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
    name: 'app:create-expense',
    description: 'Crea un gasto para una categoría',
)]
class CreateExpenseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private BudgetService $budgetService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user_id', InputArgument::REQUIRED, 'UUID del usuario')
            ->addArgument('category_id', InputArgument::REQUIRED, 'UUID de la categoría')
            ->addArgument('amount', InputArgument::REQUIRED, 'Monto del gasto')
            ->addArgument('description', InputArgument::REQUIRED, 'Descripción del gasto')
            ->addArgument('date', InputArgument::REQUIRED, 'Fecha del gasto (YYYY-MM-DD)')
            ->addArgument('merchant', InputArgument::OPTIONAL, 'Comercio/restaurante', '')
            ->addArgument('currency', InputArgument::OPTIONAL, 'Moneda', 'USD');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $userId = Uuid::fromString($input->getArgument('user_id'));
            $categoryId = Uuid::fromString($input->getArgument('category_id'));
            $amount = (float) $input->getArgument('amount');
            $description = $input->getArgument('description');
            $date = new \DateTime($input->getArgument('date'));
            $merchant = $input->getArgument('merchant') ?: null;
            $currency = $input->getArgument('currency');

            // Verificar que la categoría existe y pertenece al usuario
            $category = $this->em->getRepository(\App\Entity\Category::class)->find($categoryId);
            if (!$category || $category->getUserId()->toString() !== $userId->toString()) {
                $io->error('La categoría no existe o no pertenece al usuario');
                return Command::FAILURE;
            }

            // Verificar presupuesto antes de crear el gasto
            $budgetCheck = $this->budgetService->checkBudgetBeforeExpense($userId, $categoryId, $amount, $date);
            
            if ($budgetCheck['hasBudget']) {
                $io->section('Verificación de Presupuesto:');
                $io->table(
                    ['Métrica', 'Valor'],
                    [
                        ['Presupuesto', $budgetCheck['budgetAmount'] . ' ' . $currency],
                        ['Gastado actualmente', $budgetCheck['currentSpent'] . ' ' . $currency],
                        ['Nuevo total', $budgetCheck['newTotal'] . ' ' . $currency],
                        ['Restante', $budgetCheck['remaining'] . ' ' . $currency],
                        ['¿Excederá presupuesto?', $budgetCheck['willExceedBudget'] ? '⚠️ SÍ' : '✅ NO'],
                    ]
                );

                if (!empty($budgetCheck['warnings'])) {
                    $io->warning('Advertencias:');
                    foreach ($budgetCheck['warnings'] as $warning) {
                        $io->text($warning);
                    }
                }
            }

            // Crear el gasto
            $expense = new Expense();
            $expense->setUserId($userId);
            $expense->setCategoryId($categoryId);
            $expense->setAmount($amount);
            $expense->setCurrency($currency);
            $expense->setDescription($description);
            $expense->setOccurredAt($date);
            $expense->setMerchant($merchant);

            $this->em->persist($expense);
            $this->em->flush();

            $io->success('Gasto creado exitosamente!');
            $io->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $expense->getId()->toString()],
                    ['Categoría', $category->getName()],
                    ['Monto', $expense->getAmount() . ' ' . $expense->getCurrency()],
                    ['Descripción', $expense->getDescription()],
                    ['Fecha', $expense->getOccurredAt()->format('Y-m-d')],
                    ['Comercio', $expense->getMerchant() ?: 'N/A'],
                ]
            );

            // Mostrar estado actualizado del presupuesto
            if ($budgetCheck['hasBudget']) {
                $io->section('Estado del Presupuesto Actualizado:');
                $io->table(
                    ['Métrica', 'Valor'],
                    [
                        ['Presupuesto total', $budgetCheck['budgetAmount'] . ' ' . $currency],
                        ['Total gastado', $budgetCheck['newTotal'] . ' ' . $currency],
                        ['Restante', ($budgetCheck['budgetAmount'] - $budgetCheck['newTotal']) . ' ' . $currency],
                        ['Porcentaje usado', round(($budgetCheck['newTotal'] / $budgetCheck['budgetAmount']) * 100, 2) . '%'],
                    ]
                );
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error al crear el gasto: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

