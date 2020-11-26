<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

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

class TaskGroup extends AbstractController
{

    protected $authenticator;

    protected $responseManager;


    public function __construct(TaskooAuthenticator $authenticator, TaskooResponseManager $responseManager)
    {
        $this->authenticator = $authenticator;
        $this->responseManager = $responseManager;
    }


    /**
     * @Route("/taskgroup", name="api_taskgroup_add", methods={"POST"})
     */
    public function addTaskGroup(Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        if(isset($userId) && isset($token)) {
            $auth = $this->authenticator->checkUserAuth($userId, $token);

            if(isset($auth['user'])) {

                $entityManager = $this->getDoctrine()->getManager();
                $payload = json_decode($request->getContent(), true);

                if(!empty($payload)) {
                    $projectId = $payload['projectId'];
                    $groupName = $payload['name'];
                    $position = $payload['position'];
                    $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);

                    if($project) {
                        $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                        if(isset($auth['user'])) {
                            $taskGroup = new TaskGroups();
                            $taskGroup->setName($groupName);
                            $taskGroup->setProject($project);
                            $taskGroup->setPosition($position);
                            $taskGroup->setCreatedAt(new \DateTime('now'));

                            $entityManager->persist($taskGroup);

                            $project->addTaskGroup($taskGroup);

                            $entityManager->persist($project);
                            $entityManager->flush();

                            $data['createdId'] = $taskGroup->getId();
                            return $this->responseManager->successResponse($data, 'group_created');
                        }
                    }
                }

            } else {
                $this->responseManager->unauthorizedResponse();
            }
        }

        return $this->responseManager->forbiddenResponse();
    }


    /**
     * @Route("/taskgroup/{groupId}", name="api_taskgroup_update", methods={"PUT"})
     */
    public function updateTaskgroup(int $groupId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        if(isset($userId) && isset($token)) {
            $auth = $this->authenticator->checkUserAuth($userId, $token);
            if(isset($auth['user'])) {
                $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

                if($taskGroup) {
                    $project = $taskGroup->getProject();
                    $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                    if(isset($auth['user'])) {
                        $entityManager = $this->getDoctrine()->getManager();
                        $payload = json_decode($request->getContent(), true);

                        if(!empty($payload)) {

                            if(isset($payload['name'])) {
                                $taskGroup->setName($payload['name']);
                            }

                            if(isset($payload['taskPositions'])) {
                                $positions = $payload['taskPositions'];
                                foreach($positions as $position=>$id) {
                                    $task = $this->getDoctrine()->getRepository(Tasks::class)->find($id);
                                    $task->setPosition($position);
                                    $entityManager->persist($task);
                                }
                            }

                            $entityManager->persist($taskGroup);
                            $entityManager->flush();

                            return $this->responseManager->successResponse($data, 'taskgroup_updated');
                        }
                    }
                }

            } else {
                return $this->responseManager->unauthorizedResponse();
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/taskgroup/{groupId}", name="api_taskgroup_delete", methods={"DELETE"})
     */
    public function deleteTaskgroup(int $groupId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        if(isset($userId) && isset($token)) {
            $auth = $this->authenticator->checkUserAuth($userId, $token);
            if(isset($auth['user'])) {
                $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

                if($taskGroup) {
                    $project = $taskGroup->getProject();
                    $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                    if(isset($auth['user'])) {
                        $entityManager = $this->getDoctrine()->getManager();

                        $entityManager->remove($taskGroup);
                        $entityManager->flush();

                        return $this->responseManager->successResponse($data, 'taskgroup_deleted');
                    }
                }

            } else {
                return $this->responseManager->unauthorizedResponse();
            }
        }

        return $this->responseManager->forbiddenResponse();
    }


    /**
     * @Route("/taskgroup/{groupId}", name="api_taskgroup_get", methods={"GET"})
     */
    public function getTaskgroup(int $groupId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        if(isset($userId) && isset($token)) {
            $auth = $this->authenticator->checkUserAuth($userId, $token);
            if(isset($auth['user'])) {
                $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

                if($taskGroup) {
                    $project = $taskGroup->getProject();
                    $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                    if(isset($auth['user'])) {
                        $doneTasks = $request->query->get('done');

                        if($doneTasks === 'true') {
                            $data['tasks'] = $this->getDoctrine()->getRepository(Tasks::class)->getDoneTasks($groupId);
                        } elseif ($doneTasks === 'false') {
                            $data['tasks'] = $this->getDoctrine()->getRepository(Tasks::class)->getOpenTasks($groupId);
                        }


                        return $this->responseManager->successResponse($data, 'taskgroup_loaded');
                    }
                }

            } else {
                return $this->responseManager->unauthorizedResponse();
            }
        }

        return $this->responseManager->forbiddenResponse();
    }
}