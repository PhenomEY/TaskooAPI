<?php
namespace App\Controller;

mb_http_output('UTF-8');
//date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\Favorites;
use App\Entity\Projects;
use App\Entity\TaskGroups;
use App\Entity\Tasks;
use App\Exception\InvalidRequestException;
use App\Security\TaskooAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Project extends TaskooApiController
{
    /**
     * @Route("/project/{projectId}", name="api_project_load", methods={"GET"})
     * @param int $projectId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProject(int $projectId, Request $request)
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


        $data['groups'] = $project->getTaskgroups()
            ->map(function($group) {
                $taskData = [];
                $tasks = $group->getTasks();
                if($tasks) {
                    /** @var Tasks $task */
                    foreach($tasks as $key => &$task) {
                        $taskData[$key] = [
                            'name' => $task->getName(),
                            'id' => $task->getId(),
                            'isDone' => $task->getDone(),
                            'dateDue' => $task->getDateDue()
                        ];

                        if($task->getDescription()) {
                            $taskData[$key]['description'] = true;
                        }

                        $subTasks = $this->tasksRepository()->getSubTasks($task->getId());
                        if($subTasks) {
                            $taskData[$key]['subTasks'] = true;
                        }

                        $users = $task->getAssignedUser();
                        if($users->count() > 0) {
                            $taskData[$key]['user'] = $users->first()->getUserData();
                        }

                        if($this->mediaRepository()->findOneBy(['task' => $task->getId()])) {
                            $taskData[$key]['hasFiles'] = true;
                        }
                    }
                }

                $data['tasks'] = $taskData;

                return [
                    'name' => $group->getName(),
                    'id' => $group->getId(),
                    'tasks' => $data['tasks']
                ];
            })->toArray();

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
        $organisationId = $payload['organisationId'];

        //check if user is permitted to create a project in this organisation
        $organisation = $this->authenticator->checkOrganisationPermission($auth, $organisationId);

        //Create new Project
        $project = new Projects();
        $project->setName($payload['projectName']);

        $project->setOrganisation($organisation);

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
     */
    public function updateProject(int $projectId, Request $request)
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
                $mainUser = $this->userRepository()->find($payload['mainUser']);

                if($mainUser) $project->setMainUser($mainUser);
            }

            if(isset($payload['deadline'])) {
                $dateTime = new \DateTime($payload['deadline']);
                $project->setDeadline($dateTime);
            }

            if(isset($payload['addUser'])){
                $user = $this->userRepository()->find($payload['addUser']);

                if($user && !$project->getProjectUsers()->contains($user)) {
                    $project->addProjectUser($user);
                } else {
                    return $this->responseManager->errorResponse('adduser_failed');
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
}