<?php

namespace Taskoo\Repository;

use Taskoo\Entity\TeamRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TeamRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeamRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method TeamRole[]    findAll()
 * @method TeamRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamRole::class);
    }

    // /**
    //  * @return TeamRole[] Returns an array of TeamRole objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TeamRole
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
