<?php
namespace Taskoo\Controller;

mb_http_output('UTF-8');

use Taskoo\Api\TaskooApiController;
use Taskoo\Entity\User;
use Taskoo\Exception\NotAuthorizedException;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Struct\AuthStruct;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Taskoo\Entity\UserAuth;

class Auth extends TaskooApiController
{
    /**
     * @Route("/auth/login", name="api_auth_login", methods={"POST"})
     */
    public function Login(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();

        if(empty($payload)) {
           throw new InvalidRequestException();
        }
        
        $loginData = $payload['login'];

        $hashedPassword = $this->authenticator->generatePasswordHash($loginData['password']);

        /**
         * @var $user User
         */
        $user = $this->userRepository()->findOneBy([
            'email' => $loginData['username'],
            'password' => $hashedPassword,
            'active' => true
        ]);

        if($user === null) {
            throw new NotAuthorizedException();
        }
        
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
        $data = $this->getAppData($userAuth);

        $data['auth'] = $userAuth->getToken();



        $user->setLastLogin();

        $entityManager->persist($userAuth);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'login_success');
    }

    /**
     * @Route("/auth/check", name="api_auth_check", methods={"GET"})
     */
    public function Check(Request $request)
    {
        $token = $request->headers->get('authorization');

        /** @var UserAuth $userAuth */
        $userAuth = $this->getDoctrine()->getRepository(UserAuth::class)->findOneBy([
            'token' => $token
        ]);
        
        if(!$userAuth || !$userAuth->getUser()->getActive()) {
            throw new NotAuthorizedException();
        }
        
        //auth token is still valid
        $data = $this->getAppData($userAuth);

        return $this->responseManager->successResponse($data, 'auth_valid');
    }

    private function getAppData(UserAuth $userAuth) : array {
        $data = [];
        $user = $userAuth->getUser();

        $data['user'] = $user->getUserData();

        $userPermissions = $user->getUserPermissions();

        $data['user']['permissions']['administration'] = $userPermissions->getAdministration();
        $data['user']['permissions']['project_create'] = $userPermissions->getProjectCreate();
        $data['user']['permissions']['project_edit'] = $userPermissions->getProjectEdit();

        if($user->getUserPermissions()->getAdministration()) {
            $teams = $this->teamRepository()->findAll();
        } else {
            $teams = $user->getTeams();
        }

        foreach($teams as $key=>$team) {
            $data['teams'][$key] = $team->getTeamData();
        }

        return $data;
    }
}
