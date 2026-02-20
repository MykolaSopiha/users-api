<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AllowMockObjectsWithoutExpectations]
class ExceptionSubscriberTest extends TestCase
{
    private ExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new ExceptionSubscriber();
    }

    public function testSubscribedToKernelException(): void
    {
        $events = ExceptionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertIsArray($events[KernelEvents::EXCEPTION]);
    }

    public function testSetsJsonResponseForNotFoundHttpException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $exception = new NotFoundHttpException('User not found');

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"User not found"}', $response->getContent());
    }

    public function testSetsJsonResponseForBadRequestHttpException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $exception = new BadRequestHttpException('login is required');

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"login is required"}', $response->getContent());
    }

    public function testSetsJsonResponseForAccessDeniedHttpException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $exception = new AccessDeniedHttpException('Access Denied.');

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Access Denied."}', $response->getContent());
    }

    public function testSetsJsonResponseForGenericException(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $exception = new \RuntimeException('Internal error');

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"An error occurred"}', $response->getContent());
    }
}
