<?php

namespace App\Repository;

use App\Entity\TaskGroups;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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

    /**
    * @return TaskGroups[] Returns an array of TaskGroups objects
    */

    public function findArrayByProject($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.project = :val')
            ->setParameter('val', $value)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);
    }



//    public function findByProjectId($value): ?TaskGroups
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('project_id = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//
//    }

}
