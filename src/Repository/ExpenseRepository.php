<?php

namespace App\Repository;

use App\Entity\Expense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Expense>
 */
class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    /**
     * Get all expenses for a company ordered by newest first
     */
    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->setParameter('company', $companyId)
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get expenses by category 
     */
    public function findByCategory(int $companyId, string $category): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->andWhere('e.category = :category')
            ->setParameter('company', $companyId)
            ->setParameter('category', $category)
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}