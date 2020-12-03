<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooResponseManager;
use App\Entity\Organisations;
use App\Entity\Projects;
use App\Entity\TaskGroups;
use App\Entity\Tasks;
use App\Entity\User;
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
                if($payload['mainTaskId']) {
                    $mainTaskId = $payload['mainTaskId'];
                    $mainTask = $this->getDoctrine()->getRepository(Tasks::class)->find($mainTaskId);
                    $taskGroup = $mainTask->getTaskGroup();

                } else {
                    $groupId = $payload['groupId'];
                    $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);
                }

                if($taskGroup) {
                    $projectId = intval($payload['projectId']);
                    $taskName = $payload['taskName'];
                    $project = $taskGroup->getProject();

                    if($projectId === $project->getId()) {
                        $auth = $this->authenticator->checkUserAuth($userId, $token, $project);

                        if(isset($auth['user'])) {
                            if(isset($mainTask)) {
                                $this->increaseSubPositions($mainTaskId);
                            } else {
                                $this->increasePositions($taskGroup->getId());
                            }

                            $task = new Tasks();
                            $task->setName($taskName);
                            $task->setPosition(0);
                            $task->setDone(false);
                            $task->setCreatedBy($auth['user']);
                            $task->setCreatedAt(new \DateTime('now'));

                            if(isset($mainTask)) {
                                $task->setMainTask($mainTask);
                            }

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
                                if($payload['dateDue'] === 'null') {
                                    $task->setDateDue(null);
                                } else {
                                    $task->setDateDue(new \DateTime($payload['dateDue']));
                                }
                            }

                            if(isset($payload['addUser'])) {
                                $user = $this->getDoctrine()->getRepository(User::class)->find($payload['addUser']);

                                //check if user is permitted to work in task
                                if($user) {
                                    $check = $this->authenticator->checkUserTaskAssignment($project, $user);
                                    if($check) {
                                        $task->addAssignedUser($user);
                                    } else {
                                        $this->responseManager->unauthorizedResponse();
                                    }
                                }
                            }

                            if(isset($payload['removeUser'])) {
                                $user = $this->getDoctrine()->getRepository(User::class)->find($payload['removeUser']);

                                $task->removeAssignedUser($user);
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

                            if(isset($payload['subTaskPositions'])) {
                                $positions = $payload['subTaskPositions'];
                                foreach($positions as $position=>$id) {
                                    $task = $this->getDoctrine()->getRepository(Tasks::class)->find($id);
                                    $task->setPosition($position);
                                    $entityManager->persist($task);
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
                        $getSubTasks = $request->query->get('subTasks');

                        //collect data for app
                        $data['task']['id'] = $taskId;
                        $data['task']['name'] = $task->getName();
                        $data['task']['description'] = $task->getDescription();
                        $data['task']['dateDue'] = $task->getDateDue();
                        $data['task']['isDone'] = $task->getDone();
                        $data['task']['subTasks'] = null;
                        $data['task']['projectName'] = $project->getName();

                        $mainTask = $task->getMainTask();
                        if($mainTask) {
                            $data['task']['mainTaskId'] = $mainTask->getId();
                            $data['task']['mainTask'] = $mainTask->getName();
                        }

                        if($project->getClosed()) {
                            $data['task']['availableUsers'] = $this->getDoctrine()->getRepository(Projects::class)->getProjectUsers($project->getId());
                        } else {
                            $data['task']['availableUsers'] = $this->getDoctrine()->getRepository(Organisations::class)->getOrganisationUsers($project->getOrganisation()->getId());
                        }

                        if($task->getDone() === true) {
                            $data['task']['doneBy'] = [
                                'firstname' => $task->getDoneBy()->getFirstname(),
                                'lastname' => $task->getDoneBy()->getLastname(),
                                'id' => $task->getDoneBy()->getId()
                            ];
                        }

                        if($getSubTasks === 'true') {
                            $data['task']['subTasks'] = $this->getDoctrine()->getRepository(Tasks::class)->getSubTasks($task->getId());

                            foreach($data['task']['subTasks'] as &$subTask) {
                                if($subTask['description']) {
                                    $subTask['description'] = true;
                                }
                            }
                        }

                        $data['task']['users'] = $this->getDoctrine()->getRepository(Tasks::class)->getAssignedUsers($task->getId());

                        $data['task']['doneAt'] = $task->getDoneAt();

                        return $this->responseManager->successResponse($data, 'task_loaded');
                    }
                }
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    private function increaseSubPositions($mainTaskId) {
        $this->getDoctrine()->getRepository(Tasks::class)->increaseSubPositionsByOne($mainTaskId);
    }

    private function increasePositions($groupId) {
        $this->getDoctrine()->getRepository(Tasks::class)->increasePositionsByOne($groupId);
    }


}