<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\User;
use App\Entity\UserAuth;
use App\Entity\UserRights;
use App\Exception\InvalidRequestException;
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
        $auth = $this->authenticator->verifyToken($token);
        $isDashboard = $request->query->get('dashboard');

        if($isDashboard === 'true') {
            $notifications = $this->notificationsRepository()->getUserNotifications($auth->getUser(), true);
            $data['notifications'] = $notifications;
        } else {
            $notifications = $this->notificationsRepository()->getUserNotifications($auth->getUser());
            $data['notifications'] = $notifications;
        }


        return $this->responseManager->successResponse($data, 'notifications_loaded');
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
        $auth = $this->authenticator->verifyToken($token);

        $dashboard = false;
        $limit = 100;
        $doneTasks = 0;
        $isDashboard = $request->query->get('dashboard');
        $isDoneTasks = $request->query->get('done');

        if($isDashboard === 'true') {
            $dashboard = true;
            $limit = 20;
        }

        if($isDoneTasks === 'true') {
            $doneTasks = 1;
        }

        $tasks = $this->tasksRepository()->getTasksForUser($auth->getUser(), $dashboard, $doneTasks, $limit);

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


    /**
     * @Route("/user", name="api_user_create", methods={"POST"})
     */
    public function createUser(Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();
        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token, 'ADMINISTRATION');

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
            return $this->responseManager->errorResponse('email_in_use');
        }

        $hashedPassword = $this->authenticator->generatePassword($payload['password']);

        //else create new user
        $user = new User();
        $user->setEmail($payload['email']);
        $user->setPassword($hashedPassword);
        $user->setFirstname($payload['firstname']);
        $user->setLastname($payload['lastname']);
        $user->setActive(true);

        $userRights = new UserRights();
        $userRights->setDefaults($user);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($userRights);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'user_created');
    }

    /**
     * @Route("/user/{userId}", name="api_user_get", methods={"GET"})
     */
    public function getUserbyId(int $userId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);
        /** @var User $user */
        $user = $this->userRepository()->find($userId);
        if(!$user) throw new InvalidRequestException();

        $data['firstname'] = $user->getFirstname();
        $data['lastname'] = $user->getLastname();
        $data['email'] = $user->getEmail();

        if($auth->getUser()->getUserRights()->getAdministration()) {
            $data['active'] = $user->getActive();

            if(!$user->getPassword()) {
                $data['warnings']['password'] = true;
            }

            if($user->getOrganisations()->count() === 0) {
                $data['warnings']['organisations'] = true;
            }
        }

        if($user->getOrganisations()->count() > 0) {
            $organisations = $user->getOrganisations();
            foreach($organisations as $key=>$organisation) {
                $data['organisations'][$key] = [
                    'name' => $organisation->getName(),
                    'id' => $organisation->getId()
                ];
            }
        }

        return $this->responseManager->successResponse($data, 'user_loaded');
    }

    /**
     * @Route("/user/{userId}", name="api_user_update", methods={"PUT"})
     */
    public function updateUser(int $userId, Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();
        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);
        /** @var User $user */
        $user = $this->userRepository()->find($userId);
        if(!$user) throw new InvalidRequestException();

        //if requesting user is the updated user or admin
        if($user->getId() === $auth->getUser()->getId() || $auth->getUser()->getUserRights()->getAdministration()) {

            $entityManager = $this->getDoctrine()->getManager();

            if (isset($payload['password']) && !empty($payload['password'])) {
                $hashedPassword = $this->authenticator->generatePassword($payload['password']);
                $user->setPassword($hashedPassword);
            }

            if (isset($payload['email']) && ($payload['email'] !== $user->getEmail())) {
                //check if send email is already in use
                $emailInUse = $this->userRepository()->findOneBy([
                    'email' => $payload['email']
                ]);

                //check if email is valid
                if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->responseManager->errorResponse('invalid_email');
                }

                //if mail already exists return error
                if ($emailInUse) {
                    return $this->responseManager->errorResponse('email_in_use');
                }

                $user->setEmail($payload['email']);
            }

            if (isset($payload['active'])) {
                if ($payload['active'] === false) {
                    //deactivate user and remove authtoken
                    $userAuth = $this->getDoctrine()->getRepository(UserAuth::class)->findOneBy([
                        'user' => $user->getId()
                    ]);

                    if ($userAuth) $entityManager->remove($userAuth);
                }
                $user->setActive($payload['active']);
            }

            if (isset($payload['firstname']) && $payload['lastname']) {
                $user->setFirstname($payload['firstname']);
                $user->setLastname($payload['lastname']);
            }


            if (isset($payload['addOrganisation']) && $auth->getUser()->getUserRights()->getAdministration()) {
                $organisation = $this->organisationsRepository()->find($payload['addOrganisation']);
                $user->addOrganisation($organisation);
            }

            if (isset($payload['removeOrganisation']) && $auth->getUser()->getUserRights()->getAdministration()) {
                $organisation = $this->organisationsRepository()->find($payload['removeOrganisation']);
                $user->removeOrganisation($organisation);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->responseManager->successResponse($data, 'user_loaded');
        }
    }
}