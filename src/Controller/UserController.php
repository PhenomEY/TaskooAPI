<?php
namespace Taskoo\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use Taskoo\Api\ApiController;
use Taskoo\Entity\Notifications;
use Taskoo\Entity\TeamRole;
use Taskoo\Entity\User;
use Taskoo\Exception\InvalidPasswordException;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Exception\NotAuthorizedException;
use Taskoo\Service\NotificationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Taskoo\Service\UserService;
use Taskoo\Struct\AuthStruct;

class UserController extends ApiController
{

    /**
     * @Route("/user/notifications", name="api_user_get_notifications", methods={"GET"})
     */
    public function getUserNotifications(Request $request, NotificationService $notificationService)
    {
        $data = [];
        $auth = $this->authenticator->verifyToken($request);
        $isDashboard = $request->query->get('dashboard');

        if($isDashboard === 'true') {
            $data['notifications'] = $notificationService->load($auth->getUser(), 10);
        } else {
            $data['notifications'] = $notificationService->load($auth->getUser(), 10, null);
        }


        return $this->responseManager->successResponse($data, 'notifications_loaded');
    }

    /**
     * @Route("/user/notifications", name="api_user_update_notifications", methods={"PUT"})
     */
    public function visitedNotifications(Request $request)
    {
        $auth = $this->authenticator->verifyToken($request);
        $payload = $request->toArray();

        $notifications = $payload['notifications'];

        $manager = $this->getDoctrine()->getManager();

        foreach($notifications as $notification) {
            /** @var Notifications $update */
            $update = $this->notificationsRepository()->findOneBy([
                'id' => $notification['id'],
                'user' => $auth->getUser()
            ]);

            if($update) {
                $update->setVisited(true);
                $manager->persist($update);
            }
        }

        $manager->flush();
        return $this->responseManager->successResponse([], 'notifications_updated');
    }

    /**
     * @Route("/user/tasks", name="api_user_get_tasks", methods={"GET"})
     */
    public function getUserTasks(Request $request)
    {
        $data = [];

        $auth = $this->authenticator->verifyToken($request);

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

            if($this->mediaRepository()->findOneBy(['task' => $task['id']])) {
                $task['hasFiles'] = true;
            }
        }

        $data['tasks'] = $tasks;
        return $this->responseManager->successResponse($data, 'user_tasks_loaded');
    }


    /**
     * @Route("/user", name="api_user_create", methods={"POST"})
     * @param Request $request
     * @param UserService $userService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createUser(Request $request, UserService $userService)
    {
        $payload = $request->toArray();
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);

        //create user
        $userService->create($payload, true);

        return $this->responseManager->successResponse([], 'user_created');
    }

    /**
     * @Route("/user/{userId}", name="api_user_get", methods={"GET"})
     */
    public function getUserbyId(int $userId, Request $request)
    {
        $data = [];

        $auth = $this->authenticator->verifyToken($request);
        /** @var User $user */
        $user = $this->userRepository()->find($userId);
        if(!$user) throw new InvalidRequestException();

        $data['avatar'] = [];
        $data = $user->getUserData();

        if($auth->getUser()->getUserPermissions()->getAdministration()) {
            $data['active'] = $user->getActive();

            if(!$user->getPassword()) {
                $data['warnings']['password'] = true;
            }

            if($user->getTeams()->count() === 0) {
                $data['warnings']['teams'] = true;
            }

            $permissions = $user->getUserPermissions();

            $data['permissions']['administration'] = $permissions->getAdministration();
            $data['permissions']['project_edit'] = $permissions->getProjectEdit();
            $data['permissions']['project_create'] = $permissions->getProjectCreate();

            $availableRoles = $this->teamRolesRepository()->findAll();
            $data['availableRoles'] = [];

            /** @var TeamRole $role */
            foreach($availableRoles as $role) {
                $data['availableRoles'][] = [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'priority' => $role->getPriority()
                ];
            }
        }

        if($user->getTeams()->count() > 0) {
            $teams = $user->getTeams();
            foreach($teams as $key=>$team) {
                $data['teams'][$key] = [
                    'name' => $team->getName(),
                    'id' => $team->getId()
                ];
            }
        }

        return $this->responseManager->successResponse($data, 'user_loaded');
    }

    /**
     * @Route("/user/{userId}", name="api_user_update", methods={"PUT"})
     * @param int $userId
     * @param Request $request
     * @param UserService $userService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateUser(int $userId, Request $request, UserService $userService)
    {
        $data = [];
        $payload = $request->toArray();
        $auth = $this->authenticator->verifyToken($request);
        /** @var User $user */
        $user = $this->userRepository()->find($userId);
        if(!$user) throw new InvalidRequestException();

        $this->verificationDataUpdate($payload, $user, $auth);
        $userService->update($user, $payload);

        return $this->responseManager->successResponse($data, 'user_updated');
    }

    /**
     * @Route("/user/{userId}", name="api_user_delete", methods={"DELETE"})
     * @param int $userId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteUser(int $userId, Request $request, UserService $userService)
    {
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);
        $user = $this->userRepository()->find($userId);
        if(!$user) throw new InvalidRequestException();

        $userService->delete($user);

        return $this->responseManager->successResponse([], 'user_deleted');
    }


    private function verificationDataUpdate(?array $userData, User $user, AuthStruct $auth) : bool
    {
        //admin can do everything
        if($auth->getUser()->getUserPermissions()->getAdministration()) return true;

        if($auth->getUser()->getId() !== $user->getId()) throw new NotAuthorizedException();

        if(isset($userData['email']) || isset($userData['password'])) {
            if(!isset($userData['password_current'])) throw new NotAuthorizedException();
            $password = $this->authenticator->generatePasswordHash($userData['password_current']);
            if($password !== $user->getPassword()) throw new InvalidPasswordException();
        }

        if((isset($userData['active']) || isset($userData['permissions']) || isset($userData['teamRole']) || isset($userData['addTeam']) || isset($userData['removeTeam'])) && !$auth->getUser()->getUserPermissions()->getAdministration()) {
            throw new NotAuthorizedException();
        }

        return true;
    }
}