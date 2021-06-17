<?php
namespace Taskoo\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use Taskoo\Api\TaskooApiController;
use Taskoo\Entity\Notifications;
use Taskoo\Entity\TeamRole;
use Taskoo\Entity\User;
use Taskoo\Entity\UserAuth;
use Taskoo\Entity\UserPermissions;
use Taskoo\Exception\InvalidEmailException;
use Taskoo\Exception\InvalidPasswordException;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Service\TaskooNotificationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends TaskooApiController
{

    /**
     * @Route("/user/notifications", name="api_user_get_notifications", methods={"GET"})
     */
    public function getUserNotifications(Request $request, TaskooNotificationService $notificationService)
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
     */
    public function createUser(Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $auth = $this->authenticator->verifyToken($request, 'ADMINISTRATION');

        //check if email is valid
        if(!$this->authenticator->verifyEmail($payload['email'])) {
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
        $user->setColor($this->colorService->getRandomColor());

        $userPermissions = new UserPermissions();
        $userPermissions->setDefaults($user);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($userPermissions);
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
     */
    public function updateUser(int $userId, Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();
        $auth = $this->authenticator->verifyToken($request);
        /** @var User $user */
        $user = $this->userRepository()->find($userId);
        if(!$user) throw new InvalidRequestException();

        //if requesting user is the updated user or admin
        if($user->getId() === $auth->getUser()->getId() || $auth->getUser()->getUserPermissions()->getAdministration()) {

            $entityManager = $this->getDoctrine()->getManager();

            //color
            if(isset($payload['color'])) {
                $color = $this->colorsRepository()->find($payload['color']);
                $user->setColor($color);
            }

            //user actions with password verification
            if(isset($payload['password_current'])) {
                $hashedPassword = $this->authenticator->generatePassword($payload['password_current']);

                if($user->getPassword() !== $hashedPassword) throw new InvalidPasswordException();

                if (isset($payload['email']) && ($payload['email'] !== $user->getEmail())) {
                    //check if send email is already in use
                    $emailInUse = $this->userRepository()->findOneBy([
                        'email' => $payload['email']
                    ]);

                    //check if email is valid
                    if (!$this->authenticator->verifyEmail($payload['email'])) throw new InvalidEmailException();

                    //if mail already exists return error
                    if ($emailInUse) {
                        return $this->responseManager->errorResponse('email_in_use');
                    }

                    $user->setEmail($payload['email']);
                }

                if (isset($payload['password']) && !empty($payload['password'])) {
                    $hashedPassword = $this->authenticator->generatePassword($payload['password']);
                    $user->setPassword($hashedPassword);
                }
            }


            if (isset($payload['firstname']) && $payload['lastname']) {
                $user->setFirstname($payload['firstname']);
                $user->setLastname($payload['lastname']);
            }


            //administrator actions
            if($auth->getUser()->getUserPermissions()->getAdministration()) {
                $permissions = $user->getUserPermissions();

                if (isset($payload['email']) && ($payload['email'] !== $user->getEmail())) {
                    //check if send email is already in use
                    $emailInUse = $this->userRepository()->findOneBy([
                        'email' => $payload['email']
                    ]);

                    //check if email is valid
                    if (!$this->authenticator->verifyEmail($payload['email'])) throw new InvalidEmailException();

                    //if mail already exists return error
                    if ($emailInUse) {
                        return $this->responseManager->errorResponse('email_in_use');
                    }

                    $user->setEmail($payload['email']);
                }

                if (isset($payload['password']) && !empty($payload['password'])) {
                    $hashedPassword = $this->authenticator->generatePassword($payload['password']);
                    $user->setPassword($hashedPassword);
                }

                if (isset($payload['addTeam'])) {
                    $team = $this->teamRepository()->find($payload['addTeam']);
                    $user->addTeam($team);
                }

                if (isset($payload['removeTeam'])) {
                    $team = $this->teamRepository()->find($payload['removeTeam']);
                    $user->removeTeam($team);
                }

                if(isset($payload['permissions']['administration'])) {
                    $permissions->setAdministration($payload['permissions']['administration']);
                }

                if(isset($payload['permissions']['project_edit'])) {
                    $permissions->setProjectEdit($payload['permissions']['project_edit']);
                }

                if(isset($payload['permissions']['project_create'])) {
                    $permissions->setProjectCreate($payload['permissions']['project_create']);
                }

                if(isset($payload['teamRole'])) {
                    $role = $this->teamRolesRepository()->find($payload['teamRole']);
                    if(!$role) throw new InvalidRequestException();

                    $user->setTeamRole($role);
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

                $entityManager->persist($permissions);


            }
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->responseManager->successResponse($data, 'user_updated');
    }

    /**
     * @Route("/user/{userId}", name="api_user_delete", methods={"DELETE"})
     */
    public function deleteUser(int $userId, Request $request)
    {
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);
        $user = $this->userRepository()->find($userId);

        if(!$user) throw new InvalidRequestException();

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($user);
        $manager->flush();

        return $this->responseManager->successResponse([], 'user_deleted');
    }
}