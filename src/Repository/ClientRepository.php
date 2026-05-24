<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findAllAsArray(): array
    {
        return array_map(fn(Client $c) => $c->toArray(), $this->findAll());
    }

    public function search(string $query): array
    {
        $q = '%' . strtolower($query) . '%';

        return $this->createQueryBuilder('c')
            ->where('LOWER(c.clientName) LIKE :q OR LOWER(c.email) LIKE :q OR LOWER(c.phone) LIKE :q')
            ->setParameter('q', $q)
            ->getQuery()
            ->getResult();
    }
}
