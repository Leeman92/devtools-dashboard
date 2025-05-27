<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'], $data['password'], $data['name'])) {
            return new JsonResponse([
                'error' => 'Missing required fields: email, password, name'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            $this->logger->warning('Registration attempt with existing email', [
                'email' => $data['email'],
                'ip' => $request->getClientIp(),
            ]);
            
            return new JsonResponse([
                'error' => 'User with this email already exists'
            ], Response::HTTP_CONFLICT);
        }

        // Create new user
        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $user->setRoles(['ROLE_USER']);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Validate user
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'error' => 'Validation failed',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        $this->logger->info('User registered successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'ip' => $request->getClientIp(),
        ]);

        return new JsonResponse([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $this->logger->info('Login attempt started', [
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);

        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['email'], $data['password'])) {
                $this->logger->warning('Login attempt with missing fields', [
                    'has_data' => !empty($data),
                    'has_email' => isset($data['email']),
                    'has_password' => isset($data['password']),
                    'ip' => $request->getClientIp(),
                ]);
                
                return new JsonResponse([
                    'error' => 'Missing required fields: email, password'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->logger->info('Login data parsed successfully', [
                'email' => $data['email'],
                'ip' => $request->getClientIp(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse login request data', [
                'error' => $e->getMessage(),
                'content' => $request->getContent(),
                'ip' => $request->getClientIp(),
            ]);
            
            return new JsonResponse([
                'error' => 'Invalid request data'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Find user
            $this->logger->info('Looking up user by email', [
                'email' => $data['email'],
            ]);
            
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $data['email']]);

            if (!$user) {
                $this->logger->warning('Login attempt with non-existent email', [
                    'email' => $data['email'],
                    'ip' => $request->getClientIp(),
                ]);
                
                return new JsonResponse([
                    'error' => 'Invalid credentials'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $this->logger->info('User found, verifying password', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
            ]);

            // Verify password
            if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
                $this->logger->warning('Login attempt with invalid password', [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'ip' => $request->getClientIp(),
                ]);
                
                return new JsonResponse([
                    'error' => 'Invalid credentials'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $this->logger->info('Password verified, generating JWT token', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
            ]);

            // Generate JWT token
            $token = $this->jwtManager->create($user);

            $this->logger->info('User logged in successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'ip' => $request->getClientIp(),
            ]);

            return new JsonResponse([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'roles' => $user->getRoles(),
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Login process failed with exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'email' => $data['email'] ?? 'unknown',
                'ip' => $request->getClientIp(),
            ]);
            
            return new JsonResponse([
                'error' => 'Login failed due to server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // With JWT, logout is typically handled client-side by removing the token
        // But we can provide an endpoint for consistency
        return new JsonResponse([
            'message' => 'Logout successful'
        ]);
    }
} 