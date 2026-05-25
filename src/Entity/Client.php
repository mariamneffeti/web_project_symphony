<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'clients')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Company $company = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $clientName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 10, options: ['default' => 'B2C'])]
    private string $clientType = 'B2C';

    #[ORM\Column(length: 20, options: ['default' => 'Active'])]
    private string $status = 'Active';


    public function getId(): ?int { return $this->id; }

    // FIX: Add getter and setter for the Company relationship
    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): static { $this->company = $company; return $this; }

    public function getClientName(): ?string { return $this->clientName; }
    public function setClientName(string $clientName): static { $this->clientName = $clientName; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getClientType(): string { return $this->clientType; }
    public function setClientType(string $clientType): static { $this->clientType = $clientType; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'client_name'      => $this->clientName,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'address'          => $this->address,
            'client_type'      => $this->clientType,
            'status'           => $this->status,
            // Optional layout trace
            'company_id'       => $this->company ? $this->company->getId() : null,
        ];
    }
}