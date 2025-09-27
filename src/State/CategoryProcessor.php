<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        if ($operation->getName() === 'delete') {
            $category = $this->em->getRepository(Category::class)->findOneBy([
                'id' => $uriVariables['id'],
                'userId' => $user->getUserIdentifier()
            ]);
            
            if ($category) {
                $this->em->remove($category);
                $this->em->flush();
            }
            
            return null;
        }

        if ($operation->getName() === 'post') {
            $category = new Category();
            $category->setUserId(\Ramsey\Uuid\Uuid::fromString($user->getUserIdentifier()));
            $category->setName($data->name);
            $category->setType($data->type);
            $category->setColor($data->color);
            $category->setIcon($data->icon);
            $category->setIsArchived($data->isArchived);
            $category->setSortOrder($data->sortOrder);
            
            $this->em->persist($category);
            $this->em->flush();
            
            return $category;
        }

        if (in_array($operation->getName(), ['put', 'patch'])) {
            $category = $this->em->getRepository(Category::class)->findOneBy([
                'id' => $uriVariables['id'],
                'userId' => $user->getUserIdentifier()
            ]);
            
            if (!$category) {
                throw new \Exception('Category not found');
            }
            
            $category->setName($data->name);
            $category->setType($data->type);
            $category->setColor($data->color);
            $category->setIcon($data->icon);
            $category->setIsArchived($data->isArchived);
            $category->setSortOrder($data->sortOrder);
            $category->setUpdatedAt(new \DateTimeImmutable());
            
            $this->em->flush();
            
            return $category;
        }

        return $data;
    }
}
