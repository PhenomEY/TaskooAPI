<?php

namespace App\Repository;

use App\Entity\UserRights;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserRights|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRights|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRights[]    findAll()
 * @method UserRights[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRightsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRights::class);
    }

    // /**
    //  * @return UserRights[] Returns an array of UserRights objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserRights
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
