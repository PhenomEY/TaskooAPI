<?php

namespace App\Repository;

use App\Entity\Tasks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

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

    public function increaseSubPositionsByOne($mainTaskId)
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position+1')
            ->andWhere('t.mainTask = :mainTask')
            ->setParameter('mainTask', $mainTaskId)
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
            ->andWhere('t.mainTask IS NULL')
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
            ->select('t.name, t.id, t.done as isDone, t.dateDue, t.description')
            ->andWhere('t.TaskGroup = :group')
            ->andWhere('t.done = :done')
            ->andWhere('t.mainTask IS NULL')
            ->setParameter('group', $groupId)
            ->setParameter('done', false)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getAssignedUsers($id)
    {
        return $this->createQueryBuilder('p')
            ->select('u.id, u.firstname, u.lastname')
            ->andWhere('p.id = :task')
            ->join('p.assignedUser', 'u')
            ->setParameter('task', $id)
            ->getQuery()
            ->getResult()
            ;
    }

    public function getSubTasks($id)
    {
        return $this->createQueryBuilder('p')
            ->select('s.id, s.name, s.description, s.done as isDone, s.doneAt, s.dateDue')
            ->andWhere('p.id = :task')
            ->join('p.subTasks', 's')
            ->setParameter('task', $id)
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getTasksForUser($user,$dashboard = false, $limit = 100, $done = 0)
    {


        $queryBuilder = $this->createQueryBuilder('t')
            ->select('t.id, t.name, t.dateDue, t.description, t.done as isDone, t.doneAt, p.name as projectName, p.id as projectId, -t.dateDue as HIDDEN dateOrder')
            ->where('t.done = :done')
            ->join('t.assignedUser', 'au', Expr\Join::WITH, 'au = :user')
            ->leftJoin('t.TaskGroup', 'tg')
            ->leftJoin('tg.project', 'p')
            ->setParameter('user', $user)
            ->setParameter('done', $done)
            ->orderBy('dateOrder', 'DESC')
            ->setMaxResults($limit)
        ;

        if($dashboard === true) {
            $queryBuilder->andWhere('t.dateDue IS NOT NULL');
        }



        return $queryBuilder->getQuery()
            ->getResult();
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
