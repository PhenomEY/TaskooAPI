<?php
namespace App\Controller;

mb_http_output('UTF-8');
//date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooResponseManager;
use App\Entity\Organisations;
use App\Entity\Projects;
use App\Entity\TaskGroups;
use App\Entity\Tasks;
use App\Security\TaskooAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserAuth;

class Project extends AbstractController
{

    protected $authenticator;

    protected $responseManager;


    public function __construct(TaskooAuthenticator $authenticator, TaskooResponseManager $responseManager)
    {
        $this->authenticator = $authenticator;
        $this->responseManager = $responseManager;
    }


    /**
     * @Route("/project/{projectId}", name="api_project_load", methods={"GET"})
     */
    public function getProject(int $projectId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();

        //check if auth data got sent
        if(isset($token) && isset($userId)) {
            $auth = $this->authenticator->checkUserAuth($userId, $token);

            if(isset($auth['user'])) {
                //load project by id
                $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);
                $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

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
                                $tasks = null;

                                if(!$group->getTasks()->isEmpty()) {
                                    $tasks = $this->getDoctrine()->getRepository(Tasks::class)->getOpenTasks($group->getId());

                                    foreach($tasks as &$task) {
                                        if($task['description']) {
                                            $task['description'] = true;

                                            $users = $this->getDoctrine()->getRepository(Tasks::class)->getAssignedUsers($task['id']);
                                            if($users) {
                                                $task['user'] = $users[0];
                                            }
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
     */
    public function getProjectUsers(int $projectId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();

        //check if auth data got sent
        if(isset($token) && isset($userId)) {
            $auth = $this->authenticator->checkUserAuth($userId, $token);

            if(isset($auth['user'])) {
                //load project by id
                $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);
                $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                //if project for id was found
                if($project !== null) {
                    //authentification process
                    if(isset($auth['user'])) {
                        if($project->getClosed()) {
                            $data['users'] = $this->getDoctrine()->getRepository(Projects::class)->getProjectUsers($project->getId());
                        } else {
                            $data['users'] = $this->getDoctrine()->getRepository(Organisations::class)->getOrganisationUsers($project->getOrganisation()->getId());
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
     */
    public function createProject(Request $request, TaskooAuthenticator $authenticator)
    {
        $data = [];

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        //check if auth data got sent
        if(isset($token) && isset($userId)) {
            $auth = $authenticator->checkUserAuth($userId, $token, null,  10);

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
                    return $this->responseManager->successResponse($data, 'project_created');
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
        $userId = $request->headers->get('user');

        //check if auth data got sent
        if(isset($token) && isset($userId)) {
            //load project by id
            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);
            $auth = $this->authenticator->checkUserAuth($userId, $token, $project);
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
                                $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($id);
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