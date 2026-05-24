<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'articles')]   // must match your DB table name exactly
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'company_id')]
    private ?int $companyId = null;

    #[ORM\Column(name: 'author_name', length: 255)]
    private ?string $authorName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'ar_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $arDate = null;

    #[ORM\Column(name: 'ar_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(name: 'ar_image', length: 250, nullable: true)]
    private ?string $arImage = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    // Getters & Setters

    public function getId(): ?int { return $this->id; }

    public function getCompanyId(): ?int { return $this->companyId; }
    public function setCompanyId(int $companyId): static { $this->companyId = $companyId; return $this; }

    public function getAuthorName(): ?string { return $this->authorName; }
    public function setAuthorName(string $authorName): static { $this->authorName = $authorName; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function getArDate(): ?\DateTimeInterface { return $this->arDate; }
    public function setArDate(?\DateTimeInterface $arDate): static { $this->arDate = $arDate; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getLink(): ?string { return $this->link; }
    public function setLink(?string $link): static { $this->link = $link; return $this; }

    public function getArImage(): ?string { return $this->arImage; }
    public function setArImage(?string $arImage): static { $this->arImage = $arImage; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
}