<?php

namespace App\Entity;

use App\Repository\MeetingEmployeeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeetingEmployeeRepository::class)]
#[ORM\Table(name: 'meeting_employees')]
class MeetingEmployee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
