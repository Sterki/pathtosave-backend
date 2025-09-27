<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CategoryProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        if (isset($uriVariables['id'])) {
            return $this->em->getRepository(Category::class)->findOneBy([
                'id' => $uriVariables['id'],
                'userId' => $user->getUserIdentifier()
            ]);
        }

        return $this->em->getRepository(Category::class)->findBy([
            'userId' => $user->getUserIdentifier()
        ]);
    }
}
