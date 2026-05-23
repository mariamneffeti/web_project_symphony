<?php

namespace App\Repository;

use App\Entity\Expense;
use App\Entity\Company;
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
    public function findByCompany(Company $company): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->setParameter('company', $company)
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get expenses by category 
     */
    public function findByCategory(Company $company, string $category): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->andWhere('e.category = :category')
            ->setParameter('company', $company)
            ->setParameter('category', $category)
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}