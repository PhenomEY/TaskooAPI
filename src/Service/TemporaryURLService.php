<?php declare(strict_types=1);
namespace Taskoo\Service;

use Taskoo\Entity\TempUrls;
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
    public function generateURL(String $action, int $hours, $user = null) : TempUrls
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

    public function verifyURL($id, String $action): ?TempUrls
    {
        $tempURLRepository = $this->doctrine->getRepository(TempUrls::class);

        $currentURL = $tempURLRepository->findOneBy([
            'hash' => $id,
            'action' => $action
        ]);

        //check if url is already dead
        if(!$currentURL || $currentURL->getDeadAt() <= new \DateTime('now')) {
            return null;
        }

        return $currentURL;
    }

    public function removeURL(TempUrls $invite): void
    {
        $manager = $this->doctrine->getManager();
        $manager->remove($invite);
        $manager->flush();
    }

    private function generateHash() {
        return hash('md5', time().bin2hex(random_bytes(16)));
    }
}
