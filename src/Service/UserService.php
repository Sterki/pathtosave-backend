<?php

namespace App\Service;

use App\Entity\Profile;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;


class UserService
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function createProfileAndCategories(string $userId, string $email): void
    {
        try {
            // Verificar si el perfil ya existe
            $existingProfile = $this->em->getRepository(Profile::class)->findOneBy(['userId' => Uuid::fromString($userId)]);
            if ($existingProfile) {
                $this->logger->info('Profile already exists for user', ['userId' => $userId, 'email' => $email]);
                return;
            }

            // Convertir string a Uuid
            $uuid = Uuid::fromString($userId);

            // Crear profile
            $profile = new Profile();
            $profile->setUserId($uuid);
            $profile->setEmail($email);
            $profile->setDisplayName(explode('@', $email)[0]);
            $this->em->persist($profile);

            // Crear categorías predeterminadas
            $categories = [
                ['name' => 'Comida', 'type' => 'expense'],
                ['name' => 'Transporte', 'type' => 'expense'],
                ['name' => 'Salud', 'type' => 'expense'],
                ['name' => 'Entretenimiento', 'type' => 'expense'],
                ['name' => 'Salario', 'type' => 'income'],
                ['name' => 'Otros Ingresos', 'type' => 'income'],
            ];

            foreach ($categories as $cat) {
                $category = new Category();
                $category->setUserId($uuid);
                $category->setName($cat['name']);
                $category->setType($cat['type']);
                $this->em->persist($category);
            }

            $this->em->flush();
            $this->logger->info('Profile and categories created successfully', ['userId' => $userId, 'email' => $email]);
        } catch (\Exception $e) {
            $this->logger->error('Error creating profile and categories', [
                'userId' => $userId,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function handleWebhookUserCreation(array $webhookData): void
    {
        $userId = $webhookData['id'] ?? null;
        $email = $webhookData['email'] ?? null;

        if (!$userId || !$email) {
            throw new \InvalidArgumentException('Missing required user data from webhook');
        }

        $this->createProfileAndCategories($userId, $email);
    }
}
