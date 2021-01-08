<?php

namespace App\Repository;

use App\Entity\Notifications;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Notifications|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notifications|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notifications[]    findAll()
 * @method Notifications[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notifications::class);
    }

    public function getUserNotifications($user, $dashboard = false)
    {
        $querybuilder = $this->createQueryBuilder('n')
            ->select('n.id, n.message, n.time, bu.firstname, bu.lastname, t.name as taskName, t.id as taskId, p.name as projectName, p.id as projectId')
            ->andWhere('n.user = :user')
            ->leftJoin('n.byUser', 'bu')
            ->leftJoin('n.project', 'p')
            ->leftJoin('n.task', 't')
            ->setParameter('user', $user)
            ->orderBy('n.time', 'DESC')
            ->setMaxResults(10)
            ;

        if($dashboard === false) {
            $querybuilder->andWhere('n.visited IS NULL');
        }

        return $querybuilder->getQuery()
                            ->getResult();
    }

    public function getUserNotificationsAA($user)
    {
        return $this->createQueryBuilder('n')
            ->select('n.id, n.message, n.time, bu.firstname, bu.lastname, t.name as taskName, t.id as taskId, p.name as projectName, p.id as projectId')
            ->andWhere('n.user = :user')
            ->andWhere('n.visited IS NULL')
            ->leftJoin('n.byUser', 'bu')
            ->leftJoin('n.project', 'p')
            ->leftJoin('n.task', 't')
            ->setParameter('user', $user)
            ->orderBy('n.time', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Notifications[] Returns an array of Notifications objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Notifications
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
