<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
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
                    $data['user']['role'] = $user->getRole();
                    $data['user']['email'] = $user->getEmail();
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

            $userAuth = $this->getDoctrine()->getRepository(UserAuth::class)->findOneBy([
                'token' => $token
            ]);

            //auth token is still valid
            if($userAuth && $userAuth->getuser()->getActive()) {

                $data['user']['firstname'] = $userAuth->getUser()->getFirstname();
                $data['user']['lastname'] = $userAuth->getUser()->getLastname();
                $data['user']['id'] = $userAuth->getUser()->getId();
                $data['user']['role'] = $userAuth->getUser()->getRole();
                $data['user']['email'] = $userAuth->getUser()->getEmail();

                return $this->responseManager->successResponse($data, 'auth_valid');
            } else {
                return $this->responseManager->unauthorizedResponse();
            }

    }
}