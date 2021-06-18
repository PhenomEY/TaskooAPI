<?php declare(strict_types=1);

namespace Taskoo\Service;

use Doctrine\Persistence\ManagerRegistry;
use Taskoo\Entity\TempUrls;
use Taskoo\Entity\User;
use Taskoo\Entity\UserPermissions;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Security\Authenticator;

class InviteService {

    private $userRepository;

    private $authenticator;

    private $doctrine;

    private $userService;

    private $temporaryURLService;

    private $mailerService;

    public function __construct(ManagerRegistry $doctrine, Authenticator $authenticator, UserService $userService, TemporaryURLService $temporaryURLService, MailerService $mailerService)
    {
        $this->doctrine = $doctrine;
        $this->authenticator = $authenticator;
        $this->userRepository = $this->doctrine->getRepository(User::class);
        $this->userService = $userService;
        $this->temporaryURLService = $temporaryURLService;
        $this->mailerService = $mailerService;
    }


    public function create(array $userData)
    {
        //check if email is valid
        $this->authenticator->verifyEmail($userData['email']);

        $user = $this->userService->create($userData);

        $inviteURL = $this->temporaryURLService->generateURL($this->temporaryURLService::INVITE_ACTION, 24, $user);

        $this->mailerService->sendInviteMail($inviteURL, 24);
    }

    public function load($inviteId): TempUrls
    {
        /** @var $invite TempUrls */
        $invite = $this->temporaryURLService->verifyURL($inviteId, $this->temporaryURLService::INVITE_ACTION);

        if(!$invite) throw new InvalidRequestException();

        return $invite;
    }

    public function finish($inviteId, string $password)
    {
        /** @var TempUrls $invite */
        $invite = $this->temporaryURLService->verifyURL($inviteId, $this->temporaryURLService::INVITE_ACTION);

        if(!$invite || !isset($password)) throw new InvalidRequestException();

        $user = $invite->getUser();

        $hashedPassword = $this->authenticator->generatePassword($password);

        $user->setPassword($hashedPassword);
        $user->setActive(true);

        $entityManager = $this->doctrine->getManager();

        //set user
        $entityManager->persist($user);
        $entityManager->flush();

        $this->temporaryURLService->removeURL($invite);
    }
}