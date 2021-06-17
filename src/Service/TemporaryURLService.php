<?php
namespace Taskoo\Service;

use Taskoo\Entity\TempUrls;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class TemporaryURLService
 * @package Taskoo\Service
 */
class TemporaryURLService
{
    public const INVITE_ACTION = 'invite_action';

    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }


    /**
     * @param String $action
     * @param int $hours
     * @param null $user
     */
    public function generateURL(String $action, int $hours, $user = null)
    {
        $tempUrl = new TempUrls();
        $tempUrl->setCreatedAt(new \DateTime('now'));

        $endDate = new \DateTime('now');
        $endDate->modify("+$hours hours");
        $tempUrl->setDeadAt($endDate);
        $tempUrl->setHash($this->generateHash());
        $tempUrl->setUser($user);
        $tempUrl->setAction($action);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($tempUrl);
        $entityManager->flush();

        return $tempUrl;
    }

    public function verifyURL($id, String $action)
    {
        $tempURLRepository = $this->doctrine->getRepository(TempUrls::class);

        $currentURL = $tempURLRepository->findOneBy([
            'hash' => $id,
            'action' => $action
        ]);

        //check if url is already dead
        if(!$currentURL || $currentURL->getDeadAt() <= new \DateTime('now')) {
            return false;
        }

        return $currentURL;
    }

    private function generateHash() {
        return hash('md5', time().bin2hex(random_bytes(16)));
    }
}
