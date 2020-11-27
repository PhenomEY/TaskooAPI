<?php

namespace App\Repository;

use App\Entity\Projects;
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
            ->select('u.id, u.firstname, u.lastname')
            ->andWhere('p.id = :project')
            ->join('p.ProjectUsers', 'u')
            ->setParameter('project', $id)
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
