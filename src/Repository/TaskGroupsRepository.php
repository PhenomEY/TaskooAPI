<?php

namespace Taskoo\Repository;

use Taskoo\Entity\TaskGroups;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TaskGroups|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskGroups|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskGroups[]    findAll()
 * @method TaskGroups[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskGroupsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskGroups::class);
    }

    // /**
    //  * @return TaskGroups[] Returns an array of TaskGroups objects
    //  */



    /*
    public function findOneBySomeField($value): ?TaskGroups
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
