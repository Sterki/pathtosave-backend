<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Ramsey\Uuid\Uuid;

class ProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $userId = $user->getUserIdentifier();

        // Find existing profile
        $profile = $this->em->getRepository(Profile::class)->findOneBy(['userId' => Uuid::fromString($userId)]);
        if (!$profile) {
            throw new NotFoundHttpException('Profile not found');
        }

        // Update profile fields
        if (isset($data->displayName)) $profile->setDisplayName($data->displayName);
        if (isset($data->currency)) $profile->setCurrency($data->currency);
        if (isset($data->timezone)) $profile->setTimezone($data->timezone);

        $profile->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->convertToResource($profile);
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

