<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employees')]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 100)]
    private ?string $position = null;

    #[ORM\Column(length: 100)]
    private ?string $department = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $hireDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvPath = null;

    #[ORM\Column(name: 'user_id')]
    private ?int $userId = null;

    #[ORM\Column(name: 'company_id')]
    private ?int $companyId = null;

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getPosition(): ?string { return $this->position; }
    public function setPosition(?string $position): static { $this->position = $position; return $this; }

    public function getDepartment(): ?string { return $this->department; }
    public function setDepartment(string $department): static { $this->department = $department; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getHireDate(): ?\DateTimeInterface { return $this->hireDate; }
    public function setHireDate(?\DateTimeInterface $hireDate): static { $this->hireDate = $hireDate; return $this; }

    public function getCvPath(): ?string { return $this->cvPath; }
    public function setCvPath(?string $cvPath): static { $this->cvPath = $cvPath; return $this; }

    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(int $userId): static { $this->userId = $userId; return $this; }

    public function getCompanyId(): ?int { return $this->companyId; }
    public function setCompanyId(int $companyId): static { $this->companyId = $companyId; return $this; }
}
