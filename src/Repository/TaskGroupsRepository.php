<?php

namespace App\Repository;

use App\Entity\TaskGroups;
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

    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->select('t.name')
            ->andWhere('t.project = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY)
        ;
    }


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
