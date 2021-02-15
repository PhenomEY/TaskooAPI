<?php
namespace App\Controller;

mb_http_output('UTF-8');
//date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\Projects;
use App\Entity\TaskGroups;
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

        $entityManager = $this->getDoctrine()->getManager();

        //check if auth token got sent
        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token);

            if(isset($auth['user'])) {
                //load project by id
                $project = $this->projectsRepository()->find($projectId);
                $auth = $this->authenticator->checkUserAuth($token, $project);

                //if project for id was found
                if($project !== null) {
                    //authentification process
                    if(isset($auth['user'])) {
                        $data['project']['name'] = $project->getName();
                        $data['project']['deadline'] = $project->getDeadline();

                        $data['project']['users'] = $project->getProjectUsers()->map(function($user) {
                            return [
                                'firstname' => $user->getFirstname(),
                                'lastname' => $user->getLastname(),
                                'id' => $user->getId()
                            ];
                        })->toArray();


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
                } else {
                    return $this->responseManager->notFoundResponse();
                }
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/project/{projectId}/users", name="api_project_load_users", methods={"GET"})
     * @param int $projectId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProjectUsers(int $projectId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        //check if auth data got sent
        if(isset($token) && isset($userId)) {
            $auth = $this->authenticator->checkUserAuth($userId, $token);

            if(isset($auth['user'])) {
                //load project by id
                $project = $this->projectsRepository()->find($projectId);
                $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                //if project for id was found
                if($project !== null) {
                    //authentification process
                    if(isset($auth['user'])) {
                        if($project->getClosed()) {
                            $data['users'] = $this->projectsRepository()->getProjectUsers($project->getId());
                        } else {
                            $data['users'] = $this->organisationsRepository()->getOrganisationUsers($project->getOrganisation()->getId());
                        }

                        return $this->responseManager->successResponse($data, 'project_loaded');

                    }
                } else {
                    return $this->responseManager->notFoundResponse();
                }
            }
        }

        return $this->responseManager->forbiddenResponse();
    }


    /**
     * @Route("/project", name="api_project_create", methods={"POST"})
     * @param Request $request
     * @param TaskooAuthenticator $authenticator
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function createProject(Request $request, TaskooAuthenticator $authenticator)
    {
        $data = [];

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        $token = $request->headers->get('authorization');

        //check if auth data got sent
        if(isset($token)) {
            $auth = $authenticator->checkUserAuth($token, null,  10);

            //if payload exists
            if (!empty($payload)) {
                if(isset($auth['user'])) {
                    $projectName = $payload['projectName'];
                    $deadline = $payload['deadline'];
                    $groupName = $payload['groupName'];
                    $organisationId = 1;
                    $user = null;

                    //Create new Project
                    $project = new Projects();
                    $project->setName($projectName);
                    $dateTime = new \DateTime($deadline);
                    $project->setDeadline($dateTime);
                    $project->setCreatedAt(new \DateTime('now'));
                    $project->setClosed(true);
                    $project->addProjectUser($auth['user']);
                    $entityManager->persist($project);
                    $entityManager->flush();

                    //Create default Group
                    $taskGroup = new TaskGroups();
                    $taskGroup->setCreatedAt(new \DateTime('now'));
                    $taskGroup->setProject($project);
                    $taskGroup->setName($groupName);
                    $taskGroup->setPosition(0);
                    $entityManager->persist($taskGroup);
                    $entityManager->flush();

                    $project->addTaskGroup($taskGroup);
                    $entityManager->persist($project);
                    $entityManager->flush();

                    $data['projectId'] = $project->getId();
                    return $this->responseManager->createdResponse($data, 'project_created');
                } else {
                    return $this->responseManager->forbiddenResponse();
                }
            } else {
                return $this->responseManager->badRequestResponse();
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/project/{projectId}", name="api_project_update", methods={"PUT"})
     */
    public function updateProject(int $projectId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');

        //check if auth token got sent
        if(isset($token)) {
            //load project by id
            $project = $this->projectsRepository()->find($projectId);
            $auth = $this->authenticator->checkUserAuth($token, $project);
            $entityManager = $this->getDoctrine()->getManager();

            //if project for id was found
            if($project) {
                //authentification process
                if(isset($auth['user'])) {
                    $payload = json_decode($request->getContent(), true);

                    if(!empty($payload)) {

                        if(isset($payload['name'])) {
                            $project->setName($payload['name']);
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
            } else {
                return $this->responseManager->notFoundResponse();
            }
        }

        return $this->responseManager->forbiddenResponse();
    }
}