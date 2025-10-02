<?php
namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-tables',
    description: 'List tables in the database',
)]
class ListTables extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tables = $this->em->getConnection()->getSchemaManager()->listTableNames();
        $io = new SymfonyStyle($input, $output);

        $io->title('Listando tablas en la base de datos');
        $io->table(
            ['Tablas'],
            array_map(fn($t) => [$t], $tables) // SymfonyStyle espera un array de arrays
        );
        return Command::SUCCESS;
    }
}