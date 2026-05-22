<?php

namespace App\Repository;

use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function findBySearch(string $search): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.firstName LIKE :search OR CAST(e.id AS string) LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
