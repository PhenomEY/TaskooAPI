<?php

namespace App\Security;

use App\Entity\Organisations;
use App\Entity\UserAuth;
use Doctrine\ORM\EntityManagerInterface;


class TaskooAuthenticator {

    protected $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }


    public function checkUserAuth($userId, $token, $role = 1) {
       $userAuth = $this->manager->getRepository(UserAuth::class)->findOneBy([
           'user' => $userId,
           'token' => $token
       ]);

       if($userAuth) {
           $userRole = $userAuth->getUser()->getRole();

           //role 1 = all
           if ($role == 1) {
                return $userAuth->getUser();
           } elseif ($userRole == $role) {
               return $userAuth->getUser();
           }
       }

       return false;
    }
}