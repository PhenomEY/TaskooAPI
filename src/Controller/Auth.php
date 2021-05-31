<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Entity\User;
use App\Exception\NotAuthorizedException;
use App\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Cookie;
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

        if(empty($payload)) {
           throw new InvalidRequestException();
        }
        
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
        $data['auth'] = $userAuth->getToken();
        $data['user']['firstname'] = $user->getFirstname();
        $data['user']['lastname'] = $user->getLastname();
        $data['user']['id'] = $user->getId();
        $data['user']['email'] = $user->getEmail();
        $data['user']['avatar'] = [];

        if($user->getColor()) $data['user']['color'] = $user->getColor()->getHexCode();

        $avatar = $user->getAvatar();
        if($avatar) {
            $data['user']['avatar'] = [
                'id' => $avatar->getId(),
                'filePath' => $avatar->getFilePath(),
                'fileExtension' => $avatar->getExtension()
            ];
        }

        $userPermissions = $user->getUserPermissions();

        if($userPermissions->getAdministration()) {
            $data['user']['permissions']['administration'] = true;
        }

        if($userPermissions->getProjectCreate()) {
            $data['user']['permissions']['project_create'] = true;
        }

        if($userPermissions->getProjectEdit()) {
            $data['user']['permissions']['project_edit'] = true;
        }

        if($user->getUserPermissions()->getAdministration()) {
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

        
        if(!$userAuth || !$userAuth->getUser()->getActive()) {
            throw new NotAuthorizedException();
        }
        
        //auth token is still valid
        $data['user']['firstname'] = $userAuth->getUser()->getFirstname();
        $data['user']['lastname'] = $userAuth->getUser()->getLastname();
        $data['user']['id'] = $userAuth->getUser()->getId();
        $data['user']['email'] = $userAuth->getUser()->getEmail();
        $data['user']['avatar'] = [];

        if($userAuth->getUser()->getColor()) $data['user']['color'] = $userAuth->getUser()->getColor()->getHexCode();

        $avatar = $userAuth->getUser()->getAvatar();
        if($avatar) {
            $data['user']['avatar'] = [
                'id' => $avatar->getId(),
                'filePath' => $avatar->getFilePath(),
                'fileExtension' => $avatar->getExtension()
            ];
        }

        $userPermissions = $userAuth->getUser()->getUserPermissions();

        if($userPermissions->getAdministration()) {
            $data['user']['permissions']['administration'] = true;
        }

        if($userPermissions->getProjectCreate()) {
            $data['user']['permissions']['project_create'] = true;
        }

        if($userPermissions->getProjectEdit()) {
            $data['user']['permissions']['project_edit'] = true;
        }

        if($userAuth->getUser()->getUserPermissions()->getAdministration()) {
            $organisations = $this->organisationsRepository()->findAll();
        } else {
            $organisations = $userAuth->getUser()->getOrganisations();
        }


        foreach($organisations as $key=>$organisation) {
            $data['organisations'][$key] = $organisation->getOrganisationData();
        }

        return $this->responseManager->successResponse($data, 'auth_valid');
    }
}
