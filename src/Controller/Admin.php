<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\Settings;
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

        /** @var Settings $settings */
        $settings = $this->settingsRepository()->findAll()[0];

        $data['settings']['app_url'] = $settings->getAppUrl();
        $data['settings']['sender'] = $settings->getMailSender();

        return $this->responseManager->successResponse($data, 'mainsettings_loaded');
    }

    /**
     * @Route("/admin/main", name="api_admin_main_save", methods={"POST"})
     */
    public function saveMainSettings(Request $request)
    {
        $data = [];
        $payload = $request->toArray();
        if(!$payload) throw new InvalidRequestException();
        $auth = $this->authenticator->verifyToken($request, 'ADMINISTRATION');

        $newSettings = $payload['settings'];

        /** @var Settings $settings */
        $settings = $this->settingsRepository()->findAll()[0];
        $settings->setAppUrl($newSettings['app_url']);
        $settings->setMailSender($newSettings['sender']);

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($settings);
        $manager->flush();

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
            //default user data
            $userData = $user->getUserData();

            //sensitive user data
            $userData['active'] = $user->getActive();

            if($user->getUserPermissions()->getAdministration()) {
                $userData['isAdmin'] = true;
            }

            if(!$user->getPassword()) {
                $userData['warnings']['password'] = true;
            }

            if($user->getTeams()->count() === 0) {
                $userData['warnings']['teams'] = true;
            }

            array_push($data['users'], $userData);
            $userData = null;
        }

        return $this->responseManager->successResponse($data, 'userlist_loaded');

    }
}