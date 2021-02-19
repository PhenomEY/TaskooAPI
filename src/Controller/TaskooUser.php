<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\User;
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


    /**
     * @Route("/user", name="api_user_create", methods={"POST"})
     */
    public function createUser(Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $payload = json_decode($request->getContent(), true);

        if(isset($token) && isset($payload)) {
            $auth = $this->authenticator->checkUserAuth($token, null, $this->authenticator::IS_ADMIN);

            if(isset($auth['user'])) {
                //check if email is valid
                if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->responseManager->errorResponse('invalid_email');
                }

                //check if send email is already in use
                $emailInUse = $this->userRepository()->findOneBy([
                    'email' => $payload['email']
                ]);

                //if mail already exists return error
                if($emailInUse) {
                    return $this->responseManager->errorResponse('already_registered');
                }

                $hashedPassword = $this->authenticator->generatePassword($payload['password']);

                //else create new user
                $user = new User();
                $user->setEmail($payload['email']);
                $user->setPassword($hashedPassword);
                $user->setFirstname($payload['firstname']);
                $user->setLastname($payload['lastname']);
                $user->setRole(1);
                $user->setActive(true);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->responseManager->successResponse($data, 'user_created');
            }
        }

        return $this->responseManager->forbiddenResponse();
    }
}