<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findBySearch(string $search): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.title LIKE :s OR a.authorName LIKE :s')
            ->setParameter('s', '%' . $search . '%')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
