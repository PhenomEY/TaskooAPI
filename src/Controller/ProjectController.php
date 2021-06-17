<?php
namespace Taskoo\Controller;

mb_http_output('UTF-8');
//date_default_timezone_set('Europe/Amsterdam');

use Taskoo\Api\TaskooApiController;
use Taskoo\Entity\Favorites;
use Taskoo\Entity\Projects;
use Taskoo\Entity\TaskGroups;
use Taskoo\Entity\Tasks;
use Taskoo\Entity\User;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Security\TaskooAuthenticator;
use Taskoo\Service\TaskGroupService;
use Taskoo\Service\TaskooNotificationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends TaskooApiController
{
    /**
     * @Route("/project/{projectId}", name="api_project_load", methods={"GET"})
     * @param int $projectId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProject(int $projectId, Request $request, TaskGroupService $taskGroupService)
    {

        $data = [];
        $auth = $this->authenticator->verifyToken($request);
        //check if user got permission to view project
        $project = $this->authenticator->checkProjectPermission($auth, $projectId);

        $data['project'] = $project->getProjectMainData();
        $data['project']['isFavorite'] = false;

        if($this->favoritesRepository()->findOneBy([
            'project' => $project->getId(),
            'user' => $auth->getUser()->getId()
        ])) {
            $data['project']['isFavorite'] = true;
        };


        if($project->getClosed()) {
            $data['project']['users'] = $project->getProjectUsersData();
        }

        $data['project']['mainUser'] = $project->getMainUserData();

        $data['groups'] = [];
        $taskGroups = $project->getTaskGroups();
        foreach($taskGroups as $key => $taskGroup) {
            $data['groups'][$key] = [
                'id' => $taskGroup->getId(),
                'name' => $taskGroup->getName()
            ];
            $data['groups'][$key]['tasks'] = $taskGroupService->loadTasks($taskGroup, false);
        }

        return $this->responseManager->successResponse($data, 'project_loaded');
    }

    /**
     * @Route("/project", name="api_project_create", methods={"POST"})
     * @param Request $request
     * @param TaskooAuthenticator $authenticator
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function createProject(Request $request)
    {
        $data = [];

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $auth = $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_PROJECT_CREATE);
        $teamId = $payload['teamId'];

        //check if user is permitted to create a project in this team
        $team = $this->authenticator->checkTeamPermission($auth, $teamId);

        //Create new Project
        $project = new Projects();
        $project->setName($payload['projectName']);

        $project->setTeam($team);

        if(isset($payload['deadline'])) {
            $dateTime = new \DateTime($payload['deadline']);
            $project->setDeadline($dateTime);
        }

        if(isset($payload['mainUser'])) {
            $mainUser = $this->userRepository()->find($payload['mainUser']);
            $project->setMainUser($mainUser);
            $project->addProjectUser($mainUser);
        }

        $project->setClosed($payload['closed']);
        $project->addProjectUser($auth->getUser());
        $entityManager->persist($project);
        $entityManager->flush();

        $data['projectId'] = $project->getId();
        return $this->responseManager->createdResponse($data, 'project_created');

    }

    /**
     * @Route("/project/{projectId}", name="api_project_update", methods={"PUT"})
     * @param int $projectId
     * @param Request $request
     * @param TaskooNotificationService $notificationService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function updateProject(int $projectId, Request $request, TaskooNotificationService $notificationService)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $auth = $this->authenticator->verifyToken($request);
        $project = $this->authenticator->checkProjectPermission($auth, $projectId);

        $entityManager = $this->getDoctrine()->getManager();

        //if user has rights to edit projects or is admin
        if($auth->getUser()->getUserPermissions()->getAdministration() || $auth->getUser()->getUserPermissions()->getProjectEdit()) {
            if(isset($payload['name'])) {
                $project->setName($payload['name']);
            }
            if(isset($payload['isClosed'])) {
                $project->setClosed($payload['isClosed']);
            }

            if(isset($payload['description'])) {
                $project->setDescription($payload['description']);
            }

            if(isset($payload['mainUser'])) {
                /** @var User $mainUser */
                $mainUser = $this->userRepository()->find($payload['mainUser']);
                if($mainUser) $project->setMainUser($mainUser);

                if($mainUser->getId() !== $auth->getUser()->getId()) {
                    //generate notification
                    $notificationService->create($mainUser, $auth->getUser(), null, $project, $notificationService::PROJECT_ASSIGNED);
                }
            }

            if(isset($payload['deadline'])) {
                $dateTime = new \DateTime($payload['deadline']);
                $project->setDeadline($dateTime);
            }

            if(isset($payload['addUser'])){
                /** @var User $user */
                $user = $this->userRepository()->find($payload['addUser']);

                if($user && !$project->getProjectUsers()->contains($user)) {
                    $project->addProjectUser($user);
                } else {
                    return $this->responseManager->errorResponse('adduser_failed');
                }

                if($user->getId() !== $auth->getUser()->getId()) {
                    //generate new notification
                    $notificationService->create($user, $auth->getUser(), null, $project, $notificationService::PROJECT_ASSIGNED);
                }
            }

            if(isset($payload['removeUser'])){
                $user = $this->userRepository()->find($payload['removeUser']);

                if($user && $project->getProjectUsers()->contains($user)) {
                    $project->removeProjectUser($user);

                    //if removed user was mainuser, remove him too
                    if($project->getMainUser()) {
                        if($project->getMainUser()->getId() === $user->getId()) {
                            $project->setMainUser(null);
                        }
                    }
                }
            }
        }

        if(isset($payload['groupPositions'])) {
            $positions = $payload['groupPositions'];

            foreach($positions as $position=>$id) {
                $taskGroup = $this->taskGroupsRepository()->find($id);
                $taskGroup->setPosition($position);
                $entityManager->persist($taskGroup);
            }
        }

        $entityManager->persist($project);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'project_updated');
    }

    /**
     * @Route("/project/favorite/{projectId}", name="api_project_favorize", methods={"POST"})
     */
    public function favorizeProject(int $projectId, Request $request)
    {
        $data = [];
        $auth = $this->authenticator->verifyToken($request);
        $project = $this->authenticator->checkProjectPermission($auth, $projectId);
        $entityManager = $this->getDoctrine()->getManager();

        $favorite = new Favorites();
        $favorite->setProject($project);
        $favorite->setUser($auth->getUser());
        $favorite->setPosition(0);
        $entityManager->persist($favorite);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'favorite_added');
    }

    /**
     * @Route("/project/favorite/{projectId}", name="api_project_defavorize", methods={"DELETE"})
     */
    public function defavorizeProject(int $projectId, Request $request)
    {
        $data = [];
        $auth = $this->authenticator->verifyToken($request);
        $project = $this->authenticator->checkProjectPermission($auth, $projectId);
        $entityManager = $this->getDoctrine()->getManager();

        $favorite = $this->favoritesRepository()->findOneBy([
            'project' => $project->getId(),
            'user' => $auth->getUser()->getId()
        ]);

        if(!$favorite) throw new InvalidRequestException();

        $entityManager->remove($favorite);
        $entityManager->flush();
        return $this->responseManager->successResponse($data, 'favorite_removed');
    }

    /**
     * @Route("/favorites", name="api_project_favorite_update", methods={"PUT"})
     */
    public function updateFavoritesPositions(Request $request)
    {
        $data = [];

        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $this->authenticator->verifyToken($request);
        $entityManager = $this->getDoctrine()->getManager();

        if(isset($payload['positions'])) {
            $positions = $payload['positions'];
            foreach($positions as $position=>$id) {
                $favorite = $this->favoritesRepository()->find($id);

                $favorite->setPosition($position);
                $entityManager->persist($favorite);
            }
        }

        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'favorites_updated');
    }

    /**
     * @Route("/project/{projectId}", name="api_project_delete", methods={"DELETE"})
     * @param int $projectId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function deleteProject(int $projectId, Request $request)
    {
        $auth = $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_PROJECT_CREATE);
        $project = $this->authenticator->checkProjectPermission($auth, $projectId);

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($project);
        $manager->flush();

        return $this->responseManager->successResponse([], 'project_deleted');
    }
}