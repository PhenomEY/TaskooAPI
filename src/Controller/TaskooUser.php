<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooResponseManager;
use App\Entity\Notifications;
use App\Entity\Tasks;
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
                $isDashboard = $request->query->get('dashboard');

                if($isDashboard === 'true') {
                    $notifications = $this->getDoctrine()->getRepository(Notifications::class)->getUserNotifications($auth['user'], true);
                    $data['notifications'] = $notifications;
                } else {
                    $notifications = $this->getDoctrine()->getRepository(Notifications::class)->getUserNotifications($auth['user']);
                    $data['notifications'] = $notifications;
                }


                return $this->responseManager->successResponse($data, 'notifications_loaded');
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/user/notifications", name="api_user_update_notifications", methods={"PUT"})
     */
    public function visitedNotifications(Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');

        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token);

            if(isset($auth['user'])) {

                return $this->responseManager->successResponse($data, 'notifications_updated');
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/user/tasks", name="api_user_get_tasks", methods={"GET"})
     */
    public function getUserTasks(Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');

        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token);

            if(isset($auth['user'])) {
                $dashboard = false;
                $isDashboard = $request->query->get('dashboard');

                if($isDashboard === 'true') {
                    $dashboard = true;
                }

                $tasks = $this->getDoctrine()->getRepository(Tasks::class)->getTasksForUser($auth['user'], $dashboard);

                foreach($tasks as &$task) {
                    if($task['description']) {
                        $task['description'] = true;
                    }
                }

                $data['tasks'] = $tasks;
                return $this->responseManager->successResponse($data, 'user_tasks_loaded');
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

}