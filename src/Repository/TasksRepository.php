<?php

namespace App\Repository;

use App\Entity\Tasks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tasks|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tasks|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tasks[]    findAll()
 * @method Tasks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TasksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tasks::class);
    }

    // /**
    //  * @return Tasks[] Returns an array of Tasks objects
    //  */

    public function increasePositionsByOne($value)
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position+1')
            ->andWhere('t.TaskGroup = :group')
            ->setParameter('group', $value)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function decreasePositionsByOne($value)
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position-1')
            ->andWhere('t.TaskGroup = :group')
            ->setParameter('group', $value)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }

    public function getDoneTasks($groupId)
    {
        return $this->createQueryBuilder('t')
            ->select('t.name, t.id, t.done as isDone, b.firstname as doneByfirstName, b.lastname as doneBylastName, t.doneAt')
            ->andWhere('t.TaskGroup = :group')
            ->andWhere('t.done = :done')
            ->join('t.doneBy', 'b')
            ->setParameter('group', $groupId)
            ->setParameter('done', true)
            ->orderBy('t.doneAt', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getOpenTasks($groupId)
    {
        return $this->createQueryBuilder('t')
            ->select('t.name, t.id, t.done as isDone')
            ->andWhere('t.TaskGroup = :group')
            ->andWhere('t.done = :done')
            ->setParameter('group', $groupId)
            ->setParameter('done', false)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /*
    public function findOneBySomeField($value): ?Tasks
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
