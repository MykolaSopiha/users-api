<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Security\UserVoter;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/v1/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    #[Route('', name: 'api_users_get', methods: ['GET'])]
    public function get(Request $request): JsonResponse
    {
        $user = $this->userService->getById($request->query->get('id') ?? '');
        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        return new JsonResponse([
            'login' => $user->getLogin(),
            'pass' => $user->getPass(),
            'phone' => $user->getPhone(),
        ]);
    }

    #[Route('', name: 'api_users_post', methods: ['POST'])]
    public function post(Request $request): JsonResponse
    {
        $user = $this->userService->create($this->extractJsonBody($request));

        return new JsonResponse([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'pass' => $user->getPass(),
            'phone' => $user->getPhone(),
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_users_put', methods: ['PUT'])]
    public function put(Request $request): JsonResponse
    {
        $data = $this->extractJsonBody($request);
        $user = $this->userService->getById((string) ($data['id'] ?? ''));

        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        $updatedUser = $this->userService->update($user, $data);

        return new JsonResponse(['id' => $updatedUser->getId()]);
    }

    #[Route('', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $user = $this->userService->getById($request->query->get('id') ?? '');
        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);

        $this->userService->delete($user);

        return new JsonResponse(new \stdClass());
    }

    private function extractJsonBody(Request $request): array
    {
        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        $data = json_decode($content, true);

        return \is_array($data) ? $data : [];
    }
}
