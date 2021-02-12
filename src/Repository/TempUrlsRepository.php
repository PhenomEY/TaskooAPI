<?php

namespace App\Repository;

use App\Entity\TempUrls;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TempUrls|null find($id, $lockMode = null, $lockVersion = null)
 * @method TempUrls|null findOneBy(array $criteria, array $orderBy = null)
 * @method TempUrls[]    findAll()
 * @method TempUrls[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TempUrlsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TempUrls::class);
    }

    // /**
    //  * @return TempUrls[] Returns an array of TempUrls objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TempUrls
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
