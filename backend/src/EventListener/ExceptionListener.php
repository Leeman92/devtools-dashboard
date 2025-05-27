<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Exception listener to log all exceptions and provide consistent error responses.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
final readonly class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private string $environment,
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Determine status code
        $statusCode = $exception instanceof HttpExceptionInterface 
            ? $exception->getStatusCode() 
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        // Log the exception with full context
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request_uri' => $request->getRequestUri(),
            'request_method' => $request->getMethod(),
            'request_ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'status_code' => $statusCode,
        ];

        // Add request body for POST requests (but sanitize sensitive data)
        if ($request->isMethod('POST')) {
            $content = $request->getContent();
            if ($content) {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    // Remove sensitive fields
                    unset($data['password'], $data['token'], $data['secret']);
                    $context['request_data'] = $data;
                }
            }
        }

        // Log based on severity
        if ($statusCode >= 500) {
            $this->logger->critical('Server error occurred', $context);
        } elseif ($statusCode >= 400) {
            $this->logger->warning('Client error occurred', $context);
        } else {
            $this->logger->info('Exception occurred', $context);
        }

        // Create error response
        $errorData = [
            'error' => true,
            'message' => $this->environment === 'prod' 
                ? 'An error occurred' 
                : $exception->getMessage(),
            'status_code' => $statusCode,
            'timestamp' => new \DateTimeImmutable(),
        ];

        // Add debug information in non-production environments
        if ($this->environment !== 'prod') {
            $errorData['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        $response = new JsonResponse($errorData, $statusCode);
        $event->setResponse($response);
    }
} 