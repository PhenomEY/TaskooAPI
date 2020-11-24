<?php

namespace App\Security;

use App\Entity\Organisations;
use App\Entity\Projects;
use App\Entity\UserAuth;
use Doctrine\ORM\EntityManagerInterface;


class TaskooAuthenticator {

    protected $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }



    public function checkUserAuth($userId, $token, Projects $project = null, $role = 1) {
       $userAuth = $this->manager->getRepository(UserAuth::class)->findOneBy([
           'user' => $userId,
           'token' => $token
       ]);

       if($userAuth) {
           $authData = [
             'user' => null,
             'type' => null
           ];

           //check if user got admin role
           $userRole = $userAuth->getUser()->getRole();
           if($userRole == 10) {
               $authData['user'] = $userAuth->getUser();
               $authData['type'] = 'is_admin';

               return $authData;
           }

           if($project) {
               //check if user is permitted in project
               $user = $this->checkProjectPermission($project, $userAuth);

               return $user;
           }

           //role 1 = all
           if ($role == 1) {
               $authData['user'] = $userAuth->getUser();
               $authData['type'] = 'is_loggedin';

               return $authData;
           } elseif ($userRole == $role) {
               $authData['user'] = $userAuth->getUser();
               $authData['type'] = 'role_'.$role;

               return $authData;
           }
       }

       return false;
    }

    private function checkProjectPermission(Projects $project, UserAuth $auth) {

        $authData = [
            'user' => null,
            'type' => null
        ];

        $user = $auth->getUser();

        //check if project is closed (only for assigned users)
        if($project->getClosed() === true) {

            //check if user is assigned to project
            $projectUsers = $project->getProjectUsers();
            if($projectUsers->contains($user)) {
                //return assigned user

                $authData['user'] = $auth->getUser();
                $authData['type'] = 'is_in_project';

                return $authData;
            }
        } else {
            //project is visible for whole organisation - check if user is part of organisation
            $organisationUsers = $project->getOrganisation()->getUsers();
            if($organisationUsers->contains($user)) {
                $authData['user'] = $auth->getUser();
                $authData['type'] = 'project_open';
                return $authData;
            }
        }

        return false;
    }
}