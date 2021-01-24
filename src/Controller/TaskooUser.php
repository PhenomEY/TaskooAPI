<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TaskooUser extends TaskooApiController
{

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
                    $notifications = $this->notificationsRepository()->getUserNotifications($auth['user'], true);
                    $data['notifications'] = $notifications;
                } else {
                    $notifications = $this->notificationsRepository()->getUserNotifications($auth['user']);
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
                $doneTasks = 0;
                $isDashboard = $request->query->get('dashboard');
                $isDoneTasks = $request->query->get('done');

                if($isDashboard === 'true') {
                    $dashboard = true;
                }

                if($isDoneTasks === 'true') {
                    $doneTasks = 1;
                }

                $tasks = $this->tasksRepository()->getTasksForUser($auth['user'], $dashboard, $doneTasks);

                foreach($tasks as &$task) {
                    if($task['description']) {
                        $task['description'] = true;
                    }

                    $subTasks = $this->tasksRepository()->getSubTasks($task['id']);
                    if($subTasks) {
                        $task['subTasks'] = true;
                    }
                }

                $data['tasks'] = $tasks;
                return $this->responseManager->successResponse($data, 'user_tasks_loaded');
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

}