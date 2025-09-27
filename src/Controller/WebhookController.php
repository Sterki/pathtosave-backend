<?php

namespace App\Controller;

use App\Dto\WebhookDto;
use App\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/webhook', name: 'webhook_')]
class WebhookController extends AbstractController
{
    private UserService $userService;
    private LoggerInterface $logger;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        UserService $userService,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->userService = $userService;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('/supabase', name: 'supabase', methods: ['POST'])]
    public function supabaseWebhook(Request $request): JsonResponse
    {
        try {
            $this->logger->info('Supabase webhook received', [
                'headers' => $request->headers->all(),
                'content_type' => $request->getContentType()
            ]);

            // Obtener el contenido del request
            $content = $request->getContent();
            $this->logger->info('Webhook payload', ['payload' => $content]);

            if (empty($content)) {
                return new JsonResponse(['error' => 'Empty payload'], Response::HTTP_BAD_REQUEST);
            }

            // Decodificar el JSON
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON payload', ['error' => json_last_error_msg()]);
                return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            // Verificar si es un evento de creación de usuario
            if ($this->isUserCreatedEvent($data)) {
                $this->logger->info('User creation event detected', ['data' => $data]);
                
                $userData = $data['record'] ?? [];
                $userId = $userData['id'] ?? null;
                $email = $userData['email'] ?? null;

                if ($userId && $email) {
                    $this->userService->handleWebhookUserCreation($userData);
                    
                    $this->logger->info('User profile and categories created successfully', [
                        'userId' => $userId,
                        'email' => $email
                    ]);

                    return new JsonResponse([
                        'status' => 'success',
                        'message' => 'User profile and categories created successfully',
                        'userId' => $userId,
                        'email' => $email
                    ]);
                } else {
                    $this->logger->warning('Missing user data in webhook', ['data' => $data]);
                    return new JsonResponse(['error' => 'Missing user data'], Response::HTTP_BAD_REQUEST);
                }
            }

            // Si no es un evento de creación de usuario, solo logueamos
            $this->logger->info('Non-user-creation event received', ['type' => $data['type'] ?? 'unknown']);
            
            return new JsonResponse(['status' => 'ignored', 'message' => 'Event not handled']);

        } catch (\Exception $e) {
            $this->logger->error('Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function isUserCreatedEvent(array $data): bool
    {
        return isset($data['type']) && 
               $data['type'] === 'INSERT' && 
               isset($data['table']) && 
               $data['table'] === 'auth.users';
    }
}

