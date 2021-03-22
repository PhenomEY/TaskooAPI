<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\UserAuth;

class Auth extends TaskooApiController
{


    /**
     * @Route("/auth/login", name="api_auth_login", methods={"POST"})
     */
    public function Login(Request $request)
    {
        $data = [];

        $payload = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();

        //if payload exists
        if (!empty($payload)) {
            $loginData = $payload['login'];

            $hashedPassword = $this->authenticator->generatePassword($loginData['password']);

            /**
             * @var $user User
             */
            $user = $this->userRepository()->findOneBy([
                'email' => $loginData['username'],
                'password' => $hashedPassword,
                'active' => true
            ]);

            //if user found
            if ($user !== null) {
                    $userAuth = $this->getDoctrine()->getRepository(UserAuth::class)->findOneBy([
                        'user' => $user->getId()
                    ]);

                    //no token found for user
                    if($userAuth == null) {
                        //Generate new UserAuth
                        $userAuth = new UserAuth();
                        $userAuth->setUser($user);
                        $userAuth->setToken($this->authenticator->generateAuthToken($user->getEmail()));
                    } else {
                        //save new generated logintoken to user
                        $userAuth->setToken($this->authenticator->generateAuthToken($user->getEmail()));
                    }

                    //return data for app
                    $data['auth'] = $userAuth->getToken();
                    $data['user']['firstname'] = $user->getFirstname();
                    $data['user']['lastname'] = $user->getLastname();
                    $data['user']['id'] = $user->getId();
                    $data['user']['email'] = $user->getEmail();

                    $userRights = $user->getUserRights();

                    if($userRights->getAdministration()) {
                        $data['user']['permissions']['administration'] = true;
                    }

                    if($userRights->getProjectCreate()) {
                        $data['user']['permissions']['projectCreate'] = true;
                    }

                    if($userRights->getProjectEdit()) {
                        $data['user']['permissions']['projectEdit'] = true;
                    }



                    if($user->getUserRights()->getAdministration()) {
                        $organisations = $this->organisationsRepository()->findAll();
                    } else {
                        $organisations = $user->getOrganisations();
                    }


                    foreach($organisations as $key=>$organisation) {
                        $data['organisations'][$key] = [
                            'name' => $organisation->getName(),
                            'id' => $organisation->getId(),
                        ];

                        if($organisation->getColor()) {
                            $data['organisations'][$key]['color'] = $organisation->getColor()->getHexCode();
                        }
                    }

                    $user->setLastLogin();

                    $entityManager->persist($userAuth);
                    $entityManager->flush();

                return $this->responseManager->successResponse($data, 'login_success');
            } else {

                return $this->responseManager->unauthorizedResponse();
            }
        }


    }


    /**
     * @Route("/auth/check", name="api_auth_check", methods={"GET"})
     */
    public function Check(Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');

        /** @var UserAuth $userAuth */
        $userAuth = $this->getDoctrine()->getRepository(UserAuth::class)->findOneBy([
            'token' => $token
        ]);

        //auth token is still valid
        if($userAuth && $userAuth->getUser()->getActive()) {

            $data['user']['firstname'] = $userAuth->getUser()->getFirstname();
            $data['user']['lastname'] = $userAuth->getUser()->getLastname();
            $data['user']['id'] = $userAuth->getUser()->getId();
            $data['user']['email'] = $userAuth->getUser()->getEmail();

            $userRights = $userAuth->getUser()->getUserRights();

            if($userRights->getAdministration()) {
                $data['user']['permissions']['administration'] = true;
            }

            if($userRights->getProjectCreate()) {
                $data['user']['permissions']['projectCreate'] = true;
            }

            if($userRights->getProjectEdit()) {
                $data['user']['permissions']['projectEdit'] = true;
            }

            if($userAuth->getUser()->getUserRights()->getAdministration()) {
                $organisations = $this->organisationsRepository()->findAll();
            } else {
                $organisations = $userAuth->getUser()->getOrganisations();
            }


            foreach($organisations as $key=>$organisation) {
                $data['organisations'][$key] = [
                    'name' => $organisation->getName(),
                    'id' => $organisation->getId(),
                ];

                if($organisation->getColor()) {
                    $data['organisations'][$key]['color'] = $organisation->getColor()->getHexCode();
                }
            }

            return $this->responseManager->successResponse($data, 'auth_valid');
        } else {
            return $this->responseManager->unauthorizedResponse();
        }

    }
}