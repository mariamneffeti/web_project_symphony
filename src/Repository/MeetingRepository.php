<?php

namespace App\Repository;

use App\Entity\Meeting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MeetingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meeting::class);
    }

    public function findUpcomingForEmployee(int $employeeId): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.employees', 'e')
            ->where('e.id = :eid')
            ->andWhere('m.meetingDate >= :today')
            ->andWhere('m.status = :status')
            ->setParameter('eid', $employeeId)
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('status', 'scheduled')
            ->orderBy('m.meetingDate', 'ASC')
            ->addOrderBy('m.meetingTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCompany(int $companyId): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.company', 'c')
            ->where('c.id = :cid')
            ->setParameter('cid', $companyId)
            ->orderBy('m.meetingDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}