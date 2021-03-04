<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\Notifications;
use App\Entity\Tasks;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Task extends TaskooApiController
{

    /**
     * @Route("/task", name="api_task_add", methods={"POST"})
     */
    public function addTask(Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');

        if(isset($token)) {
            $entityManager = $this->getDoctrine()->getManager();
            $payload = json_decode($request->getContent(), true);

            if(!empty($payload)) {
                if(isset($payload['mainTaskId'])) {
                    $mainTaskId = $payload['mainTaskId'];
                    $mainTask = $this->tasksRepository()->find($mainTaskId);
                    $taskGroup = $mainTask->getTaskGroup();

                } else {
                    $groupId = $payload['groupId'];
                    $taskGroup = $this->taskGroupsRepository()->find($groupId);
                }

                if($taskGroup) {
                    $taskName = $payload['taskName'];
                    $project = $taskGroup->getProject();

                        $auth = $this->authenticator->checkUserAuth($token, $project);

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

                            return $this->responseManager->createdResponse($data, 'task_created');

                        } else {
                            return $this->responseManager->unauthorizedResponse();
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

        $data = [];

        //check if auth data was sent
        if(isset($token)) {
            $entityManager = $this->getDoctrine()->getManager();
            $payload = json_decode($request->getContent(), true);

            if(!empty($payload)) {


                /**
                 * @var $task Tasks
                 */
                $task = $this->tasksRepository()->find($taskId);
                $project = $task->getTaskGroup()->getProject();

                    $auth = $this->authenticator->checkUserAuth($token, $project);

                    if(isset($auth['user'])) {
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
                                $user = $this->userRepository()->find($payload['addUser']);

                                //check if user is permitted to work in task
                                if($user) {
                                    $check = $this->authenticator->checkUserTaskAssignment($project, $user);
                                    if($check) {
                                        $task->addAssignedUser($user);

                                        if($user !== $auth['user']) {

                                            $notification = new Notifications();
                                            $notification->setTask($task);
                                            $notification->setByUser($auth['user']);
                                            $notification->setUser($user);
                                            $notification->setTime(new \DateTime('now'));
                                            $notification->setMessage('task_assigned');

                                            $entityManager->persist($notification);
                                        }


                                    } else {
                                        $this->responseManager->unauthorizedResponse();
                                    }
                                }
                            }

                            if(isset($payload['removeUser'])) {
                                $user = $this->userRepository()->find($payload['removeUser']);

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
                                    $task = $this->tasksRepository()->find($id);
                                    $task->setPosition($position);
                                    $entityManager->persist($task);
                                }
                            }

                            if(isset($payload['priority'])) {
                                $task->setHigherPriority($payload['priority']);
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

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/task/{taskId}", name="api_task_load", methods={"GET"})
     */
    public function getTask(int $taskId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');

        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token);

            if(isset($auth['user'])) {
                //get Task
                /**
                 * @var $task Tasks
                 */
                $task = $this->tasksRepository()->find($taskId);

                if($task) {
                    $project = $task->getTaskGroup()->getProject();
                    //check if user is permitted to see the task
                    $auth = $this->authenticator->checkUserAuth($token, $project);
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
                        $data['task']['projectId'] = $project->getId();
                        $data['task']['highPriority'] = $task->getHigherPriority();




                        $mainTask = $task->getMainTask();
                        if($mainTask) {
                            $data['task']['mainTaskId'] = $mainTask->getId();
                            $data['task']['mainTask'] = $mainTask->getName();
                        }

                        if($project->getClosed()) {
                            $data['task']['availableUsers'] = $this->projectsRepository()->getProjectUsers($project->getId());
                        } else {
                            $data['task']['availableUsers'] = $this->organisationsRepository()->getOrganisationUsers($project->getOrganisation()->getId());
                        }

                        if($task->getDone() === true) {
                            $data['task']['doneBy'] = [
                                'firstname' => $task->getDoneBy()->getFirstname(),
                                'lastname' => $task->getDoneBy()->getLastname(),
                                'id' => $task->getDoneBy()->getId()
                            ];
                        }

                        if($getSubTasks === 'true') {
                            $data['task']['subTasks'] = $this->tasksRepository()->getSubTasks($task->getId());

                            foreach($data['task']['subTasks'] as &$subTask) {
                                if($subTask['description']) {
                                    $subTask['description'] = true;
                                }
                            }
                        }

                        $data['task']['users'] = $this->tasksRepository()->getAssignedUsers($task->getId());

                        $data['task']['doneAt'] = $task->getDoneAt();

                        return $this->responseManager->successResponse($data, 'task_loaded');
                    }
                }
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/task/{taskId}", name="api_task_delete", methods={"DELETE"})
     */
    public function deleteTask(int $taskId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');

        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token);

            if(isset($auth['user'])) {
                //get Task
                $task = $this->tasksRepository()->find($taskId);

                if($task) {
                    $project = $task->getTaskGroup()->getProject();
                    //check if user is permitted to see the task
                    $auth = $this->authenticator->checkUserAuth($token, $project);
                    if(isset($auth['user'])) {
                        $entityManager = $this->getDoctrine()->getManager();
                        $entityManager->remove($task);
                        $entityManager->flush();
                        return $this->responseManager->successResponse($data, 'task_deleted');
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