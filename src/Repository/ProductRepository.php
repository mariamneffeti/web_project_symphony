<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Get all products ordered by name.
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.productName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get products with low stock (qty <= threshold).
     */
    public function findLowStock(int $threshold = 20): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stockQuantity > 0')
            ->andWhere('p.stockQuantity <= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('p.stockQuantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get out-of-stock products.
     */
    public function findOutOfStock(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.stockQuantity <= 0')
            ->getQuery()
            ->getResult();
    }
}