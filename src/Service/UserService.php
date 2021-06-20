<?php declare(strict_types=1);

namespace Taskoo\Service;

use Doctrine\Persistence\ManagerRegistry;
use Taskoo\Entity\Color;
use Taskoo\Entity\Team;
use Taskoo\Entity\TeamRole;
use Taskoo\Entity\User;
use Taskoo\Entity\UserPermissions;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Security\Authenticator;

class UserService {

    private $doctrine;

    private $userRepository;

    private $colorService;

    private $authenticator;

    private $colorRepository;

    private $teamRepository;

    private $teamRoleRepository;

    public function __construct(ManagerRegistry $doctrine, ColorService $colorService, Authenticator $authenticator)
    {
        $this->doctrine = $doctrine;
        $this->userRepository = $this->doctrine->getRepository(User::class);
        $this->colorService = $colorService;
        $this->authenticator = $authenticator;
        $this->colorRepository = $this->doctrine->getRepository(Color::class);
        $this->teamRepository = $this->doctrine->getRepository(Team::class);
        $this->teamRoleRepository = $this->doctrine->getRepository(TeamRole::class);
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

    public function update(User $user, ?array $userData) : User
    {
        if(!$userData || !$user) throw new InvalidRequestException();
        
        //color
        if(isset($userData['color'])) {
            $color = $this->colorRepository->find($userData['color']);
            $user->setColor($color);
        }

    
        if (isset($userData['email']) && ($userData['email'] !== $user->getEmail())) {
            //check if email is valid
            $this->authenticator->verifyEmail($userData['email']);
            $user->setEmail($userData['email']);
        }

        if (isset($userData['password'])) {
            $hashedPassword = $this->authenticator->generatePassword($userData['password']);
            $user->setPassword($hashedPassword);
        }
        

        if (isset($userData['firstname'])) {
            $user->setFirstname($userData['firstname']);
        }

        if(isset($userData['lastname'])) {
            $user->setLastname($userData['lastname']);
        }


        $permissions = $user->getUserPermissions();

        if (isset($userData['email']) && ($userData['email'] !== $user->getEmail())) {
            //check if email is valid
            $this->authenticator->verifyEmail($userData['email']);

            $user->setEmail($userData['email']);
        }

        if (isset($userData['password']) && !empty($userData['password'])) {
            $hashedPassword = $this->authenticator->generatePassword($userData['password']);
            $user->setPassword($hashedPassword);
        }

        if (isset($userData['addTeam'])) {
            $team = $this->teamRepository->find($userData['addTeam']);
            $user->addTeam($team);
        }

        if (isset($userData['removeTeam'])) {
            $team = $this->teamRepository->find($userData['removeTeam']);
            $user->removeTeam($team);
        }

        if(isset($userData['permissions']['administration'])) {
            $permissions->setAdministration($userData['permissions']['administration']);
        }

        if(isset($userData['permissions']['project_edit'])) {
            $permissions->setProjectEdit($userData['permissions']['project_edit']);
        }

        if(isset($userData['permissions']['project_create'])) {
            $permissions->setProjectCreate($userData['permissions']['project_create']);
        }

        if(isset($userData['teamRole'])) {
            $role = $this->teamRoleRepository->find($userData['teamRole']);
            if(!$role) throw new InvalidRequestException();

            $user->setTeamRole($role);
        }

        if (isset($userData['active'])) {
            $user->setActive($userData['active']);
        }

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($permissions);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    public function delete(User $user) : bool
    {
        $manager = $this->doctrine->getManager();
        $manager->remove($user);
        $manager->flush();

        return true;
    }
}