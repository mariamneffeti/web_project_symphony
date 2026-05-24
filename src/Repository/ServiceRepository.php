<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /**
     * Get all services belonging to a company, ordered by name.
     */
    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.company = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('s.serviceName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get a single service by ID, scoped to a company.
     */
    public function findOneByIdAndCompany(int $id, int $companyId): ?Service
    {
        return $this->createQueryBuilder('s')
            ->where('s.id = :id')
            ->andWhere('s.company = :companyId')
            ->setParameter('id', $id)
            ->setParameter('companyId', $companyId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Create and persist a new service.
     */
    public function createService(
        string $serviceName,
        float $basePrice,
        ?string $description,
        object $company
    ): Service {
        $service = new Service();
        $service->setServiceName($serviceName);
        $service->setBasePrice($basePrice);
        $service->setDescription($description);
        $service->setCompany($company);

        $this->getEntityManager()->persist($service);
        $this->getEntityManager()->flush();

        return $service;
    }

    /**
     * Update an existing service with provided fields.
     */
    public function updateService(Service $service, array $data): Service
    {
        if (!empty($data['service_name'])) {
            $service->setServiceName($data['service_name']);
        }

        if (isset($data['base_price'])) {
            $service->setBasePrice($data['base_price']);
        }

        if (array_key_exists('description', $data)) {
            $service->setDescription($data['description']);
        }

        $this->getEntityManager()->flush();

        return $service;
    }

    /**
     * Delete a service.
     */
    public function deleteService(Service $service): void
    {
        $this->getEntityManager()->remove($service);
        $this->getEntityManager()->flush();
    }
}