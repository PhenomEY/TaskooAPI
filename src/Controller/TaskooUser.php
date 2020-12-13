<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooResponseManager;
use App\Entity\User;
use App\Security\TaskooAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TaskooUser extends AbstractController
{
    protected $authenticator;

    protected $responseManager;


    public function __construct(TaskooAuthenticator $authenticator, TaskooResponseManager $responseManager)
    {
        $this->authenticator = $authenticator;
        $this->responseManager = $responseManager;
    }

    /**
     * @Route("/user/notifications", name="api_user_get_notifications", methods={"GET"})
     */
    public function getUserNotifications(Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');

        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token);

            if(isset($auth['user'])) {
                $notifications = [];
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

}