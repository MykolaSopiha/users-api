<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class UserVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        if ($this->hasRole($currentUser, 'ROLE_ROOT')) {
            return true;
        }

        return match ($attribute) {
            self::VIEW, self::EDIT => $currentUser->getId() === $subject->getId(),
            self::DELETE => false,
            default => false,
        };
    }

    private function hasRole(User $user, string $role): bool
    {
        return \in_array($role, $user->getRoles(), true);
    }
}
