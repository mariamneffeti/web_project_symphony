<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    private ?string $role = null;   // 'employee' | 'normal' | 'company'

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    // ── UserInterface ───────────────────────────────────────────────

    public function getRoles(): array
    {
        return match ($this->role) {
            'employee' => ['ROLE_EMPLOYEE'],
            'company'  => ['ROLE_COMPANY'],
            default    => ['ROLE_USER'],
        };
    }

    public function getUserIdentifier(): string { return (string) $this->email; }
    public function eraseCredentials(): void {}

    // ── Getters / Setters ───────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $v): static { $this->firstName = $v; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $v): static { $this->lastName = $v; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $v): static { $this->email = $v; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $v): static { $this->role = $v; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $v): static { $this->password = $v; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $v): static { $this->image = $v; return $this; }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}
