<?php

namespace Taskoo\Repository;

use Taskoo\Entity\Projects;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Projects|null find($id, $lockMode = null, $lockVersion = null)
 * @method Projects|null findOneBy(array $criteria, array $orderBy = null)
 * @method Projects[]    findAll()
 * @method Projects[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projects::class);
    }

    // /**
    //  * @return Projects[] Returns an array of Projects objects
    //  */

    public function getProjectUsers($id)
    {
        return $this->createQueryBuilder('p')
            ->select('u.id, u.firstname, u.lastname, c.hexCode, a.filePath')
            ->andWhere('p.id = :project')
            ->andWhere('u.active = :active')
            ->join('p.ProjectUsers', 'u')
            ->leftJoin('u.color', 'c')
            ->leftJoin('u.avatar', 'a')
            ->setParameter('project', $id)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult()
            ;
    }


    /*
    public function findOneBySomeField($value): ?Projects
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
