<?php declare(strict_types=1);

namespace App\Security;

use App\Entity\Team;
use App\Entity\Projects;
use App\Entity\User;
use App\Entity\UserAuth;
use App\Exception\InvalidAuthenticationException;
use App\Exception\InvalidNewPasswordException;
use App\Exception\InvalidRequestException;
use App\Exception\NoAuthenticationException;
use App\Exception\NoAuthentificationException;
use App\Exception\NotAuthorizedException;
use App\Struct\AuthStruct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;


class TaskooAuthenticator {
    public const PERMISSIONS_ADMINISTRATION = 'ADMINISTRATION';
    public const PERMISSIONS_PROJECT_CREATE = 'PROJECT_CREATE';
    public const PERMISSIONS_PROJECT_EDIT   = 'PROJECT_EDIT';

    public const IS_USER = 'IS_USER';
    public const IS_API = 'IS_API';

    protected EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function verifyToken(?Request $request, ?string $permission = null, ?int $id = null): ?AuthStruct {
        if(!$request) throw new InvalidRequestException();

        $token = $request->headers->get('authorization');
        if(!$token) throw new NoAuthentificationException();

        /** @var AuthStruct $auth */
        $auth = $this->checkAuthToken($token);

        if($permission) {
            $this->checkPermission($permission, $auth);
        }

        return $auth;
    }

    public function checkProjectPermission(AuthStruct $auth, int $projectId): Projects {
        /** @var Projects $project */
        $project = $this->manager->getRepository(Projects::class)->find($projectId);
        if(!$project) throw new InvalidRequestException();

        //if user is admin, do nothing
        if(!$auth->getUser()->getUserPermissions()->getAdministration()) {
            $user = $auth->getUser();

            if($project->getClosed()) {
                //project is closed, check if user is assigned to project
                if(!$project->getProjectUsers()->contains($user)) throw new NotAuthorizedException();
            } else {
                //project is open, check if user is assigned to organisation
                $team = $project->getTeam();
                if(!$team->getUsers()->contains($user)) throw new NotAuthorizedException();
            }
        }

        return $project;
    }

    public function checkTeamPermission(AuthStruct $auth, int $organisationId): Team {
        /** @var Team $team */
        $team = $this->manager->getRepository(Team::class)->find($organisationId);
        if(!$team) throw new InvalidRequestException();
        //if user is admin, do nothing
        if(!$auth->getUser()->getUserPermissions()->getAdministration()) {
            $user = $auth->getUser();
            if(!$team->getUsers()->contains($user)) throw new NotAuthorizedException();
        }

        return $team;
    }

    public function generatePassword($password): string {
        if(!$this->verifyPassword($password)) throw new InvalidNewPasswordException();

        $hashedPassword = hash('sha256', $password.'taskoo7312');

        return $hashedPassword;
    }

    public function generatePasswordHash($password): string {
        $hashedPassword = hash('sha256', $password.'taskoo7312');

        return $hashedPassword;
    }

    public function generateAuthToken(String $salt): string {
        return hash('sha256', time().$salt.bin2hex(random_bytes(16)));
    }

    public function verifyEmail(String $email) {
        //check if email is valid
       return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function verifyPassword(String $password): Bool {
        return (strlen($password) >= 8);
    }

    private function checkAuthToken($token): ?AuthStruct {
        /** @var UserAuth $userAuth */
       $userAuth = $this->manager->getRepository(UserAuth::class)->findOneBy([
           'token' => $token
       ]);

       //if token doesnt exist
       if(!$userAuth) throw new InvalidAuthenticationException();

       //if user is inactive
       if(!$userAuth->getUser()->getActive()) throw new NotAuthorizedException();

       $authStruct = new AuthStruct(self::IS_USER, $userAuth->getUser());

       return $authStruct;
    }

    private function checkPermission(string $permission, AuthStruct $auth): bool {
        //todo: check if api request got send
        if($auth->getType() === self::IS_USER) {
            $user = $auth->getUser();
        }

        //if user is admin, let him PASS!!
        if($user->getUserPermissions()->getAdministration()) return true;
      
        switch($permission) {
            case self::PERMISSIONS_ADMINISTRATION:
                if(!$user->getUserPermissions()->getAdministration()) throw new NotAuthorizedException();
                break;
            case self::PERMISSIONS_PROJECT_CREATE:
                if(!$user->getUserPermissions()->getProjectCreate()) throw new NotAuthorizedException();
                break;
            case self::PERMISSIONS_PROJECT_EDIT:
                if(!$user->getUserPermissions()->getProjectEdit()) throw new NotAuthorizedException();
                break;
            default:
                throw new InvalidRequestException();
        }

        return true;
    }
}
