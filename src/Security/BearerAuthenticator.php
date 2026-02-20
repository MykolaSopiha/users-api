<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Bearer token authenticator.
 * Token format: "Bearer {login}" - user is authenticated by login.
 */
class BearerAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        if (!\is_string($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('Missing or invalid Authorization header');
        }

        $token = trim(substr($authHeader, 7));
        if ('' === $token) {
            throw new AuthenticationException('Empty Bearer token');
        }

        $user = $this->userRepository->findByLogin($token);
        if (null === $user) {
            throw new AuthenticationException('Invalid token');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn () => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => $exception->getMessage()],
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json']
        );
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            ['error' => $authException?->getMessage() ?? 'Full authentication is required to access this resource.'],
            Response::HTTP_UNAUTHORIZED,
            ['Content-Type' => 'application/json']
        );
    }
}
