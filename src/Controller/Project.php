<?php
namespace App\Controller;

mb_http_output('UTF-8');
//date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\Projects;
use App\Entity\TaskGroups;
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

        $token = $request->headers->get('authorization');

        //check if authentication is valid
        $auth = $this->authenticator->verifyToken($token);

        //check if user got permission to view project
        $project = $this->authenticator->checkProjectPermission($auth, $projectId);

        $data['project']['id'] = $project->getId();
        $data['project']['name'] = $project->getName();
        $data['project']['deadline'] = $project->getDeadline();
        $data['project']['isClosed'] = $project->getClosed();

        if($project->getOrganisation()) {
            $data['project']['organisation']['id'] = $project->getOrganisation()->getId();
            $data['project']['organisation']['name'] = $project->getOrganisation()->getName();

            if($project->getOrganisation()->getColor()) {
                $data['project']['organisation']['color'] = $project->getOrganisation()->getColor()->getHexCode();
            }
        }

        if($project->getClosed()) {
            $data['project']['users'] = $this->projectsRepository()->getProjectUsers($projectId);
        }

        if($project->getMainUser()) {
            $data['project']['mainUser'] = [
                'firstname' => $project->getMainUser()->getFirstname(),
                'lastname' => $project->getMainUser()->getLastname(),
                'id' => $project->getMainUser()->getId()
            ];
        }

        $data['groups'] = $project->getTaskgroups()
            ->map(function($group) {
                $tasks = [];

                if(!$group->getTasks()->isEmpty()) {
                    $tasks = $this->tasksRepository()->getOpenTasks($group->getId());

                    foreach($tasks as &$task) {
                        if($task['description']) {
                            $task['description'] = true;
                        }

                        $subTasks = $this->tasksRepository()->getSubTasks($task['id']);
                        if($subTasks) {
                            $task['subTasks'] = true;
                        }

                        $users = $this->tasksRepository()->getAssignedUsers($task['id']);
                        if($users) {
                            $task['user'] = $users[0];
                        }
                    }

                    $data['tasks'] = $tasks;
                }

                return [
                    'name' => $group->getName(),
                    'id' => $group->getId(),
                    'tasks' => $tasks
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

        throw new InvalidRequestException();

        $token = $request->headers->get('authorization');

        $auth = $this->authenticator->verifyToken($token, 'PROJECT_CREATE');
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


        $project->setCreatedAt(new \DateTime('now'));
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

        $token = $request->headers->get('authorization');

        $auth = $this->authenticator->verifyToken($token);
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
}