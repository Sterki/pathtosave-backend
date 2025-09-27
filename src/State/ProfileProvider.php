<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Ramsey\Uuid\Uuid;

class ProfileProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $userId = $user->getUserIdentifier();

        if ($operation->getName() === 'get') {
            // Single profile - only allow getting own profile
            $profile = $this->em->getRepository(Profile::class)->findOneBy(['userId' => Uuid::fromString($userId)]);
            if (!$profile) {
                throw new NotFoundHttpException('Profile not found');
            }
            return $this->convertToResource($profile);
        }

        // Collection - only return own profile
        $profile = $this->em->getRepository(Profile::class)->findOneBy(['userId' => Uuid::fromString($userId)]);
        if (!$profile) {
            return [];
        }

        return [$this->convertToResource($profile)];
    }

    private function convertToResource(Profile $profile): object
    {
        $resource = new \App\ApiResource\ProfileResource();
        $resource->userId = $profile->getUserId()->toString();
        $resource->email = $profile->getEmail();
        $resource->displayName = $profile->getDisplayName();
        $resource->currency = $profile->getCurrency();
        $resource->timezone = $profile->getTimezone();
        $resource->createdAt = $profile->getCreatedAt()->format('Y-m-d H:i:s');
        $resource->updatedAt = $profile->getUpdatedAt()->format('Y-m-d H:i:s');

        return $resource;
    }
}

