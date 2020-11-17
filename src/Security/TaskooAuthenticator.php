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
           //check if user got admin role
           $userRole = $userAuth->getUser()->getRole();
           if($userRole === 10) {
               return $userAuth->getUser();
           }

           if($project) {
               //check if user is permitted in project
               $user = $this->checkProjectPermission($project, $userAuth);

               return $user;
           }

           //role 1 = all
           if ($role == 1) {
                return $userAuth->getUser();
           } elseif ($userRole == $role) {
               return $userAuth->getUser();
           }
       }

       return false;
    }

    private function checkProjectPermission(Projects $project, UserAuth $auth) {

        $user = $auth->getUser();

        //check if project is closed (only for assigned users)
        if($project->getClosed() === true) {

            //check if user is assigned to project
            $projectUsers = $project->getProjectUsers();
            if($projectUsers->contains($user)) {
                //return assigned user
                return $auth->getUser();
            }
        } else {
            //project is visible for whole organisation - check if user is part of organisation
            $organisationUsers = $project->getOrganisation()->getUsers();
            if($organisationUsers->contains($user)) {
                return $user;
            }
        }

        return false;
    }
}