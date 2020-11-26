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

class Task extends AbstractController
{

    protected $authenticator;

    protected $responseManager;


    public function __construct(TaskooAuthenticator $authenticator, TaskooResponseManager $responseManager)
    {
        $this->authenticator = $authenticator;
        $this->responseManager = $responseManager;
    }


    /**
     * @Route("/task", name="api_task_add", methods={"POST"})
     */
    public function addTask(Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        if(isset($userId) && isset($token)) {
            $entityManager = $this->getDoctrine()->getManager();
            $payload = json_decode($request->getContent(), true);

            if(!empty($payload)) {
                $projectId = intval($payload['projectId']);
                $groupId = $payload['groupId'];
                $taskName = $payload['taskName'];

                $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

                if($taskGroup) {
                    $project = $taskGroup->getProject();

                    if($projectId === $project->getId()) {
                        $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                        if(isset($auth['user'])) {
                            $this->increasePositions($taskGroup->getId());

                            $task = new Tasks();
                            $task->setName($taskName);
                            $task->setPosition(0);
                            $task->setDone(false);
                            $entityManager->persist($task);

                            $taskGroup->addTask($task);
                            $entityManager->persist($taskGroup);
                            $entityManager->flush();

                            $data['createdId'] = $task->getId();

                            return $this->responseManager->successResponse($data, 'task_created');

                        } else {
                            return $this->responseManager->unauthorizedResponse();
                        }
                    }
                }
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/task/{taskId}", name="api_task_update", methods={"PUT"})
     */
    public function updateTask(int $taskId, Request $request)
    {
        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $data = [];

        //check if auth data was sent
        if(isset($userId) && isset($token)) {
            $entityManager = $this->getDoctrine()->getManager();
            $payload = json_decode($request->getContent(), true);

            if(!empty($payload)) {
                $projectId = intval($payload['projectId']);

                $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);
                $project = $task->getTaskGroup()->getProject();

                if($projectId === $project->getId()) {
                    $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                    if(isset($auth['user'])) {
                        $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);

                        if($task) {
                            if(isset($payload['name'])) {
                                $task->setName($payload['name']);
                            }

                            if(isset($payload['description'])) {
                                $task->setDescription($payload['description']);
                            }

                            if(isset($payload['dateDue'])) {
                                $task->setDateDue($payload['dateDue']);
                            }

                            if(isset($payload['done'])) {
                                $task->setDone($payload['done']);

                                if($payload['done'] === true) {
                                    $task->setDoneBy($auth['user']);
                                    $task->setDoneAt(new \DateTime('now'));

                                    $data['doneAt'] = new \DateTime('now');
                                    $data['doneBy'] = [
                                        'firstname' => $auth['user']->getFirstname(),
                                        'lastname' => $auth['user']->getLastname(),
                                    ];
                                }
                            }

                            $entityManager->persist($task);
                            $entityManager->flush();

                            return $this->responseManager->successResponse($data, 'task_updated');
                        }
                    } else {
                        return $this->responseManager->unauthorizedResponse();
                    }
                }
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/task/{taskId}", name="api_task_load", methods={"GET"})
     */
    public function getTask(int $taskId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        if(isset($userId) && isset($token)) {
            $auth = $this->authenticator->checkUserAuth($userId, $token);

            if(isset($auth['user'])) {
                //get Task
                $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);

                if($task) {
                    $project = $task->getTaskGroup()->getProject();
                    //check if user is permitted to see the task
                    $auth = $this->authenticator->checkUserAuth($userId, $token, $project);
                    if(isset($auth['user'])) {
                        //collect data for app
                        $data['task']['id'] = $taskId;
                        $data['task']['name'] = $task->getName();
                        $data['task']['description'] = $task->getDescription();
                        $data['task']['dateDue'] = $task->getDateDue();
                        $data['task']['isDone'] = $task->getDone();

                        if($task->getDone() === true) {
                            $data['task']['doneBy'] = [
                                'firstname' => $task->getDoneBy()->getFirstname(),
                                'lastname' => $task->getDoneBy()->getLastname(),
                                'id' => $task->getDoneBy()->getId()
                            ];
                        }

                        $data['task']['doneAt'] = $task->getDoneAt();

                        return $this->responseManager->successResponse($data, 'task_loaded');
                    }
                }
            }
        }

        return $this->responseManager->forbiddenResponse();
    }


    private function increasePositions($groupId) {
        $this->getDoctrine()->getRepository(Tasks::class)->increasePositionsByOne($groupId);
    }


}