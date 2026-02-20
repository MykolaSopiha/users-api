<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    private const REQUIRED_FIELDS = ['login', 'pass', 'phone'];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function getById(string $id): User
    {
        $userId = $this->parseUserId($id);
        $user = $this->userRepository->find($userId);

        if (null === $user) {
            throw new NotFoundHttpException('User not found');
        }

        return $user;
    }

    public function create(array $data): User
    {
        $this->validateRequiredFields($data);

        $user = new User();
        $user->setLogin((string) $data['login']);
        $user->setPass((string) $data['pass']);
        $user->setPhone((string) $data['phone']);
        $user->setRoles(['ROLE_USER']);

        $this->validateEntity($user);

        $existing = $this->userRepository->findByLoginAndPass($user->getLogin(), $user->getPass());
        if (null !== $existing) {
            throw new BadRequestHttpException('User with this login and pass combination already exists');
        }

        $this->userRepository->persist($user);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $this->validateRequiredFields($data);

        $user->setLogin((string) $data['login']);
        $user->setPass((string) $data['pass']);
        $user->setPhone((string) $data['phone']);

        $this->validateEntity($user);

        $this->userRepository->persist($user);

        return $user;
    }

    public function delete(User $user): void
    {
        $this->userRepository->remove($user);
    }

    private function parseUserId(string $id): int
    {
        $id = trim($id);
        if ('' === $id) {
            throw new BadRequestHttpException('id is required');
        }

        $userId = filter_var($id, \FILTER_VALIDATE_INT);
        if (false === $userId || $userId <= 0) {
            throw new BadRequestHttpException('Invalid id');
        }

        return $userId;
    }

    private function validateRequiredFields(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            $value = $data[$field] ?? null;
            if (null === $value || '' === trim((string) $value)) {
                throw new BadRequestHttpException("{$field} is required");
            }
        }
    }

    private function validateEntity(User $user): void
    {
        $errors = $this->validator->validate($user);
        if (\count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors->get(0)->getMessage());
        }
    }
}
