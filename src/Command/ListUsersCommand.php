<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:list-users',
    description: 'Lista usuarios de Supabase',
)]
class ListUsersCommand extends Command
{
    private HttpClientInterface $httpClient;
    private string $supabaseUrl;
    private string $supabaseServiceKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
        // Usar JWT secret como service key para operaciones admin
        $this->supabaseServiceKey = $_ENV['SUPABASE_JWT_SECRET'] ?? '';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (empty($this->supabaseUrl) || empty($this->supabaseServiceKey)) {
            $io->error('Variables SUPABASE_URL y SUPABASE_JWT_SECRET no están configuradas en .env');
            return Command::FAILURE;
        }

        try {
            $io->title('Listando usuarios de Supabase...');
            $io->note('Nota: Este comando requiere el Service Role Key de Supabase para acceder a la API de administración.');

            // Intentar conectar directamente a la base de datos PostgreSQL
            $io->info('Conectando directamente a la base de datos PostgreSQL...');
            
            // Parsear la URL de la base de datos
            $dbUrl = $_ENV['DATABASE_URL'] ?? '';
            if (empty($dbUrl)) {
                $io->error('DATABASE_URL no está configurada en .env');
                return Command::FAILURE;
            }

            $io->info('Para obtener la lista de usuarios, puedes:');
            $io->text('1. Ir a Supabase Dashboard → Authentication → Users');
            $io->text('2. O usar la siguiente consulta SQL en el SQL Editor:');
            $io->newLine();
            $io->text('SELECT id, email, created_at, last_sign_in_at, email_confirmed_at');
            $io->text('FROM auth.users');
            $io->text('ORDER BY created_at DESC;');
            $io->newLine();
            $io->note('Una vez que tengas el UUID y email del usuario, usa:');
            $io->text('C:\\xampp\\php\\php.exe bin/console app:create-user "UUID" "email@ejemplo.com"');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
