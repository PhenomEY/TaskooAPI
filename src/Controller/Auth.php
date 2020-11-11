<?php
namespace App\Controller;

mb_http_output('UTF-8');

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserAuth;

class Auth extends AbstractController
{


    /**
     * @Route("/auth/login", name="api_auth_login")
     */
    public function Login(Request $request)
    {
        $success = false;
        $message = 'login_failed';
        $token = null;
        $userId = null;
        $firstname = null;
        $lastname = null;
        $userType = 1;

        $payload = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();

        //if payload exists
        if (!empty($payload)) {
            $loginData = $payload['login'];

            $hashedPassword = hash('sha256', $loginData['password']);


            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                'email' => $loginData['username'],
                'password' => $hashedPassword
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
                        $userAuth->setToken($this->generateToken());
                    } else {

                        //save new generated logintoken to user
                        $userAuth->setToken($this->generateToken());
                    }



                    //return data for app
                    $token = $userAuth->getToken();
                    $firstname = $user->getFirstname();
                    $lastname = $user->getLastname();
                    $userId = $user->getId();
                    $userType = $user->getRole();
                    $user->setLastLogin();

                    $entityManager->persist($userAuth);
                    $entityManager->flush();
                    $success = true;
                    $message = 'login_success';

            }


        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message,
            'userData' => [
                    'token' => $token,
                    'userid' => $userId,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'userType' => $userType
                ]
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }


    /**
     * @Route("/auth/check", name="api_auth_check")
     */
    public function Check(Request $request)
    {
        $success = false;
        $userType = 1;

        //if its the actual get request
        if ($request->getMethod() == 'GET') {
            $token = $request->headers->get('authorization');
            $userId = $request->headers->get('user');

            $userAuth = $this->getDoctrine()->getRepository(UserAuth::class)->findOneBy([
                'token' => $token,
                'user' => $userId
            ]);

            //auth token is still valid
            if($userAuth) {
                $success = true;
                $userType = $userAuth->getUser()->getRole();
            }
        }

        $response = new JsonResponse([
            'success' => $success,
            'userType' => $userType
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "GET");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }

    private function generateToken() {
        return hash('sha256', time());
    }
}