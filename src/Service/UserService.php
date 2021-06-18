<?php declare(strict_types=1);

namespace Taskoo\Service;

use Doctrine\Persistence\ManagerRegistry;
use Taskoo\Entity\User;
use Taskoo\Entity\UserPermissions;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Security\Authenticator;

class UserService {

    private $doctrine;

    private $userRepository;

    private $colorService;

    private $authenticator;

    public function __construct(ManagerRegistry $doctrine, ColorService $colorService, Authenticator $authenticator)
    {
        $this->doctrine = $doctrine;
        $this->userRepository = $this->doctrine->getRepository(User::class);
        $this->colorService = $colorService;
        $this->authenticator = $authenticator;
    }

    public function create(?array $userData, bool $active = true) : User
    {
        if(!$userData) throw new InvalidRequestException();

        //check if email is valid
        $this->authenticator->verifyEmail($userData['email']);

        $user = new User();

        //needed user data
        $user->setEmail($userData['email']);
        $user->setFirstname($userData['firstname']);
        $user->setLastname($userData['lastname']);
        $user->setColor($this->colorService->getRandomColor());

        if(isset($userData['password'])) {
            $password = $this->authenticator->generatePassword($userData['password']);
            $user->setPassword($password);
        }

        if($active) {
            $user->setActive($active);
        }

        //create permissions for user
        $permissions = new UserPermissions();
        $permissions->setDefaults($user);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->persist($permissions);
        $entityManager->flush();

        return $user;
    }
}