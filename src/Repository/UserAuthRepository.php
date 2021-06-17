<?php

namespace Taskoo\Repository;

use Taskoo\Entity\UserAuth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserAuth|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAuth|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAuth[]    findAll()
 * @method UserAuth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAuthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAuth::class);
    }

    // /**
    //  * @return UserAuth[] Returns an array of UserAuth objects
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
    public function findOneBySomeField($value): ?UserAuth
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
