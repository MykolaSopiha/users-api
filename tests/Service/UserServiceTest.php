<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AllowMockObjectsWithoutExpectations]
class UserServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    private function createService(): UserService
    {
        return new UserService($this->userRepository, $this->validator);
    }

    private function createUser(int $id = 1, string $login = 'john', string $pass = 'secret', string $phone = '12345678', array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setPass($pass);
        $user->setPhone($phone);
        $user->setRoles($roles);
        $reflection = new \ReflectionClass($user);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($user, $id);

        return $user;
    }

    public function testGetByIdReturnsUser(): void
    {
        $user = $this->createUser(1);
        $this->userRepository->expects($this->once())->method('find')->with(1)->willReturn($user);

        $service = $this->createService();
        $result = $service->getById('1');

        $this->assertSame($user, $result);
    }

    public function testGetByIdThrowsWhenUserNotFound(): void
    {
        $this->userRepository->expects($this->once())->method('find')->with(1)->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('User not found');

        $this->createService()->getById('1');
    }

    public function testGetByIdThrowsWhenIdEmpty(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('id is required');

        $this->createService()->getById('');
    }

    public function testGetByIdThrowsWhenIdInvalid(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid id');

        $this->createService()->getById('abc');
    }

    public function testGetByIdThrowsWhenIdZero(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid id');

        $this->createService()->getById('0');
    }

    public function testCreatePersistsUser(): void
    {
        $this->validator->expects($this->atLeastOnce())->method('validate')->willReturn(new ConstraintViolationList());
        $this->userRepository->expects($this->once())->method('findByLoginAndPass')->willReturn(null);

        $service = $this->createService();
        $user = $service->create(['login' => 'jane', 'pass' => 'pass123', 'phone' => '87654321']);

        $this->assertSame('jane', $user->getLogin());
        $this->assertSame('pass123', $user->getPass());
        $this->assertSame('87654321', $user->getPhone());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testCreateThrowsWhenLoginRequired(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('login is required');

        $this->createService()->create(['pass' => 'pass', 'phone' => '12345678']);
    }

    public function testCreateThrowsWhenPassRequired(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('pass is required');

        $this->createService()->create(['login' => 'john', 'phone' => '12345678']);
    }

    public function testCreateThrowsWhenPhoneRequired(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('phone is required');

        $this->createService()->create(['login' => 'john', 'pass' => 'secret']);
    }

    public function testCreateThrowsWhenLoginPassDuplicate(): void
    {
        $existing = $this->createUser();
        $this->validator->expects($this->once())->method('validate')->willReturn(new ConstraintViolationList());
        $this->userRepository->expects($this->once())->method('findByLoginAndPass')->with('john', 'secret')->willReturn($existing);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('User with this login and pass combination already exists');

        $this->createService()->create(['login' => 'john', 'pass' => 'secret', 'phone' => '12345678']);
    }

    public function testUpdatePersistsChanges(): void
    {
        $user = $this->createUser(1, 'old', 'oldpass', '11111111');
        $this->validator->expects($this->once())->method('validate')->willReturn(new ConstraintViolationList());

        $service = $this->createService();
        $result = $service->update($user, ['login' => 'new', 'pass' => 'newpass', 'phone' => '22222222']);

        $this->assertSame('new', $result->getLogin());
        $this->assertSame('newpass', $result->getPass());
        $this->assertSame('22222222', $result->getPhone());
    }

    public function testUpdateThrowsWhenLoginRequired(): void
    {
        $user = $this->createUser();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('login is required');

        $this->createService()->update($user, ['pass' => 'pass', 'phone' => '12345678']);
    }

    public function testDeleteRemovesUser(): void
    {
        $user = $this->createUser();
        $this->userRepository->expects($this->once())->method('remove')->with($user);

        $this->createService()->delete($user);
    }
}
