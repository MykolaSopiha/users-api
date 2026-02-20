<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user', options: ['charset' => 'utf8mb4'])]
#[ORM\UniqueConstraint(name: 'uniq_login_pass', columns: ['login', 'pass'])]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 8)]
    #[Assert\NotBlank(message: 'login is required')]
    #[Assert\Length(max: 8, maxMessage: 'login is too long. It should have {{ limit }} characters or less.')]
    private ?string $login = null;

    #[ORM\Column(length: 8)]
    #[Assert\NotBlank(message: 'pass is required')]
    #[Assert\Length(max: 8, maxMessage: 'pass is too long. It should have {{ limit }} characters or less.')]
    private ?string $pass = null;

    #[ORM\Column(length: 8)]
    #[Assert\NotBlank(message: 'phone is required')]
    #[Assert\Length(max: 8, maxMessage: 'phone is too long. It should have {{ limit }} characters or less.')]
    private ?string $phone = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function setPass(string $pass): static
    {
        $this->pass = $pass;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        if ([] === $roles) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }
}
