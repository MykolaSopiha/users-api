<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserVoter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
class UserVoterTest extends TestCase
{
    private UserVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new UserVoter();
    }

    private function createUser(int $id = 1, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setLogin('user' . $id);
        $user->setPass('pass');
        $user->setPhone('12345678');
        $user->setRoles($roles);
        $reflection = new \ReflectionClass($user);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($user, $id);

        return $user;
    }

    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testRoleUserCanViewOwnUserReturnsGranted(): void
    {
        $user = $this->createUser(1);
        $result = $this->voter->vote($this->createToken($user), $user, [UserVoter::VIEW]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRoleRootCanViewAnyUser(): void
    {
        $admin = $this->createUser(1, ['ROLE_ROOT']);
        $otherUser = $this->createUser(2);

        $result = $this->voter->vote($this->createToken($admin), $otherUser, [UserVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRoleRootCanEditAnyUser(): void
    {
        $admin = $this->createUser(1, ['ROLE_ROOT']);
        $otherUser = $this->createUser(2);

        $result = $this->voter->vote($this->createToken($admin), $otherUser, [UserVoter::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRoleRootCanDeleteAnyUser(): void
    {
        $admin = $this->createUser(1, ['ROLE_ROOT']);
        $otherUser = $this->createUser(2);

        $result = $this->voter->vote($this->createToken($admin), $otherUser, [UserVoter::DELETE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRoleUserCanEditOwnUser(): void
    {
        $user = $this->createUser(1);

        $result = $this->voter->vote($this->createToken($user), $user, [UserVoter::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRoleUserCannotViewOtherUser(): void
    {
        $currentUser = $this->createUser(1);
        $otherUser = $this->createUser(2);

        $result = $this->voter->vote($this->createToken($currentUser), $otherUser, [UserVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testRoleUserCannotEditOtherUser(): void
    {
        $currentUser = $this->createUser(1);
        $otherUser = $this->createUser(2);

        $result = $this->voter->vote($this->createToken($currentUser), $otherUser, [UserVoter::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testRoleUserCannotDelete(): void
    {
        $user = $this->createUser(1);

        $result = $this->voter->vote($this->createToken($user), $user, [UserVoter::DELETE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testReturnsAbstainForNonUserSubject(): void
    {
        $result = $this->voter->vote($this->createToken($this->createUser()), new \stdClass(), [UserVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testReturnsDeniedWhenTokenUserNotAppUserInstance(): void
    {
        $otherUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $otherUser->method('getUserIdentifier')->willReturn('other');
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);
        $subject = $this->createUser();

        $result = $this->voter->vote($token, $subject, [UserVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}
