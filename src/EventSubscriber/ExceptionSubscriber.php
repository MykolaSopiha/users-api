<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -128],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $message = $this->getSafeMessage($exception);
        $statusCode = $this->getStatusCode($exception);

        $response = new JsonResponse(
            ['error' => $message],
            $statusCode,
            ['Content-Type' => 'application/json']
        );

        $event->setResponse($response);
    }

    private function getSafeMessage(\Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getMessage();
        }

        $current = $exception;
        while (null !== $current) {
            if ($current instanceof UniqueConstraintViolationException) {
                return 'User with this login and pass combination already exists';
            }
            $current = $current->getPrevious();
        }

        return 'An error occurred';
    }

    private function getStatusCode(\Throwable $exception): int
    {
        return match (true) {
            $exception instanceof UnauthorizedHttpException => $exception->getStatusCode(),
            $exception instanceof AccessDeniedHttpException => Response::HTTP_FORBIDDEN,
            $exception instanceof NotFoundHttpException => Response::HTTP_NOT_FOUND,
            $exception instanceof BadRequestHttpException => Response::HTTP_BAD_REQUEST,
            $exception instanceof HttpException => $exception->getStatusCode(),
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}
