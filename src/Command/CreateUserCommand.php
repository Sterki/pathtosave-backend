<?php

namespace App\Command;

use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
{
    // Eliminamos $defaultName para evitar el error
    protected static $defaultDescription = 'Crea un usuario con profile y categorías en Supabase.';

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:create-user') // <-- Aquí registramos el nombre explícitamente
            ->addArgument('user_id', InputArgument::REQUIRED, 'UUID del usuario de Supabase')
            ->addArgument('email', InputArgument::REQUIRED, 'Email del usuario');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = $input->getArgument('user_id');
        $email = $input->getArgument('email');

        $this->userService->createProfileAndCategories($userId, $email);

        $output->writeln("Usuario creado correctamente: $email ($userId)");
        return Command::SUCCESS;
    }
}
