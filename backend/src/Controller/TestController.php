<?php

declare(strict_types=1);

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test controller for debugging logging and error handling.
 */
#[Route('/api/test')]
final class TestController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    #[Route('/logging', name: 'api_test_logging', methods: ['GET'])]
    public function testLogging(): JsonResponse
    {
        $this->logger->debug('Debug level log message');
        $this->logger->info('Info level log message', ['test' => true]);
        $this->logger->notice('Notice level log message');
        $this->logger->warning('Warning level log message', ['warning' => 'test']);
        $this->logger->error('Error level log message', ['error' => 'test']);
        $this->logger->critical('Critical level log message', ['critical' => 'test']);

        return $this->json([
            'message' => 'Logging test completed',
            'levels_tested' => ['debug', 'info', 'notice', 'warning', 'error', 'critical'],
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/error', name: 'api_test_error', methods: ['GET'])]
    public function testError(): JsonResponse
    {
        $this->logger->error('About to throw test exception');
        
        throw new \RuntimeException('This is a test exception to verify error logging');
    }

    #[Route('/500', name: 'api_test_500', methods: ['GET'])]
    public function test500(): JsonResponse
    {
        $this->logger->error('About to trigger 500 error');
        
        // Simulate the kind of error that might cause 500
        $nonExistentService = null;
        $nonExistentService->someMethod(); // This will cause a fatal error
        
        return $this->json(['message' => 'This should never be reached']);
    }

    #[Route('/auth-test', name: 'api_test_auth_logging', methods: ['POST'])]
    public function testAuthLogging(): JsonResponse
    {
        // Test authentication logging without actual authentication
        $authLogger = $this->container->get('monolog.logger.auth');
        
        $authLogger->info('Test auth info log');
        $authLogger->warning('Test auth warning log');
        $authLogger->error('Test auth error log');
        
        return $this->json([
            'message' => 'Auth logging test completed',
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/env', name: 'api_test_env', methods: ['GET'])]
    public function testEnvironment(): JsonResponse
    {
        $this->logger->info('Environment test requested');
        
        return $this->json([
            'environment' => $this->getParameter('kernel.environment'),
            'debug' => $this->getParameter('kernel.debug'),
            'log_level' => $_ENV['LOG_LEVEL'] ?? 'not set',
            'app_env' => $_ENV['APP_ENV'] ?? 'not set',
            'app_debug' => $_ENV['APP_DEBUG'] ?? 'not set',
            'jwt_secret_key' => isset($_ENV['JWT_SECRET_KEY']) ? 'set' : 'not set',
            'jwt_public_key' => isset($_ENV['JWT_PUBLIC_KEY']) ? 'set' : 'not set',
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/jwt-test', name: 'api_test_jwt', methods: ['GET'])]
    public function testJWT(): JsonResponse
    {
        try {
            // Test JWT configuration by checking if the service is available
            $this->logger->info('JWT manager service is available', [
                'jwt_manager_class' => get_class($this->jwtManager),
            ]);
            
            return $this->json([
                'message' => 'JWT configuration test completed',
                'jwt_manager_available' => true,
                'jwt_manager_class' => get_class($this->jwtManager),
                'timestamp' => new \DateTimeImmutable(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('JWT configuration test failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return $this->json([
                'message' => 'JWT configuration test failed',
                'jwt_manager_available' => false,
                'error' => $e->getMessage(),
                'timestamp' => new \DateTimeImmutable(),
            ], 500);
        }
    }
} 