<?php
namespace App\Service;

use App\Entity\TempUrls;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Doctrine\Persistence\ManagerRegistry;

class TemporaryURLService
{
    public const INVITE_ACTION = 'invite_action';

    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }


    public function generateURL(String $action, int $hours, $user = null)
    {
        $tempUrl = new TempUrls();
        $tempUrl->setCreatedAt(new \DateTime('now'));

        $endDate = new \DateTime('now');
        $endDate->modify("+$hours hours");
        $tempUrl->setDeadAt($endDate);
        $tempUrl->setHash('AAAA');
        $tempUrl->setUser($user);
        $tempUrl->setAction($action);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($tempUrl);
        $entityManager->flush();
    }

    public function verifyURL($id, String $action)
    {
        $tempURLRepository = $this->doctrine->getRepository(TempUrls::class);

        $currentURL = $tempURLRepository->findOneBy([
            'hash' => $id,
            'action' => $action
        ]);

        if($currentURL) {
            //check if url is already dead
            if($currentURL->getDeadAt() <= new \DateTime('now')) {
                return false;
            }

            return $currentURL;
        }

        return false;
    }
}