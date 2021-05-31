<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\User;
use App\Exception\InvalidRequestException;
use App\Service\TaskooMailerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;

class Admin extends TaskooApiController
{
    /**
     * @Route("/admin/main", name="api_admin_main_get", methods={"GET"})
     */
    public function getMainSettings(Request $request)
    {
        $data = [];
        $auth = $this->authenticator->verifyToken($request, 'ADMINISTRATION');

        $mainSettings = $this->settingsRepository()->findOneBy([
            'name' => 'app_url'
        ]);

        $data['app_url'] = $mainSettings->getValue();

        return $this->responseManager->successResponse($data, 'mainsettings_loaded');
    }

    /**
     * @Route("/admin/main", name="api_admin_main_save", methods={"POST"})
     */
    public function saveMainSettings(Request $request)
    {
        $data = [];

        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();
        $auth = $this->authenticator->verifyToken($request, 'ADMINISTRATION');

        $settings = $payload['settings'];

        $mainSettings = $this->settingsRepository()->findOneBy([
            'name' => 'app_url'
        ]);

        $mainSettings->setValue($settings['app_url']);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($mainSettings);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'mainsettings_saved');
    }

    /**
     * @Route("/users", name="api_users_get", methods={"GET"})
     */
    public function getUsers(Request $request)
    {
        $data = [];
        $auth = $this->authenticator->verifyToken($request, 'ADMINISTRATION');

        $users = $this->userRepository()->findAll();

        $data['users'] = [];

        /**
         * @var $user User
         */
        foreach($users as $user) {
            $userData = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'active' => $user->getActive()
            ];

            if($user->getUserPermissions()->getAdministration()) {
                $userData['isAdmin'] = true;
            }

            if(!$user->getPassword()) {
                $userData['warnings']['password'] = true;
            }

            if($user->getOrganisations()->count() === 0) {
                $userData['warnings']['organisations'] = true;
            }

            if($user->getColor()) {
                $userData['color']['hexCode'] = $user->getColor()->getHexCode();
            }

            array_push($data['users'], $userData);
            $userData = null;
        }

        return $this->responseManager->successResponse($data, 'userlist_loaded');

    }
}