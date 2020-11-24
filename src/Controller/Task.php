<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

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


    /**
     * @Route("/task/add", name="api_task_add")
     */
    public function addTask(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $createdId = null;
        $message = 'create_task_failed';

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

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
                    $auth = $authenticator->checkUserAuth($userId, $token, $project);

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

                        $createdId = $task->getId();
                        $success = true;
                        $message = 'task_created';

                    } else {
                        $message = 'permission_denied';
                    }
                }
            }
        }

        $response = new JsonResponse([
            'success' => $success,
            'createdId' => $createdId,
            'message' => $message
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }

    /**
     * @Route("/task/changeName", name="api_task_changename")
     */
    public function changeName(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $message = 'change_name_failed';

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = intval($payload['projectId']);
            $taskId = $payload['taskId'];
            $newName = $payload['newName'];

            $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);
            $project = $task->getTaskGroup()->getProject();

            if($projectId === $project->getId()) {
                $auth = $authenticator->checkUserAuth($userId, $token, $project);

                if(isset($auth['user'])) {
                    $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);

                    if($task) {
                        $task->setName($newName);
                        $entityManager->persist($task);
                        $entityManager->flush();
                        $success = true;
                        $message = 'name_changed';
                    }
                } else {
                    $message = 'permission_denied';
                }
            }
        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }

    /**
     * @Route("/task/changePositions", name="api_task_changepositions")
     */
    public function changePositions(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $message = 'change_positions_failed';

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = intval($payload['projectId']);
            $groupId = $payload['groupId'];
            $positions = $payload['positions'];

            $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

            if($taskGroup) {
                $project = $taskGroup->getProject();

                if($projectId === $project->getId()) {
                    $auth = $authenticator->checkUserAuth($userId, $token, $project);
                    if(isset($auth['user'])) {

                        foreach($positions as $position=>$id) {
                            $task = $this->getDoctrine()->getRepository(Tasks::class)->find($id);
                            $task->setPosition($position);
                            $entityManager->persist($task);
                        }

                        $entityManager->flush();
                        $success = true;
                        $message = 'positions_changed';
                    } else {
                        $message = 'permission_denied';
                    }
                }
            }
        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }

    /**
     * @Route("/task/project/get", name="api_task_getforproject")
     */
    public function getTaskForProject(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $message = 'load_task_failed';
        $data = [
            'name' => null,
            'description' => null,
            'doneBy' => [
                'firstname' => null,
                'lastname' => null
            ],
            'doneAt' => null,
            'isDone' => false
        ];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = intval($payload['projectId']);
            $taskId = $payload['taskId'];

            $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);
            $project = $task->getTaskGroup()->getProject();

            if($projectId === $project->getId()) {
                $auth = $authenticator->checkUserAuth($userId, $token, $project);
                if(isset($auth['user'])) {
                    $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);


                    if($task) {
                        //when task was found
                        //check if projectId matches the tasks project
                        if($task->getTaskGroup()->getProject()->getId() == $projectId) {
                            //collect data for app
                            $data['name'] = $task->getName();
                            $data['description'] = $task->getDescription();
                            $data['isDone'] = $task->getDone();
                            $success = true;
                        }
                    } else {
                        $success = false;
                        $message = 'task_not_found';
                    }

                }
            }
        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message,
            'task' => $data
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }

    /**
     * @Route("/task/done", name="api_task_done")
     */
    public function setTaskDone(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $message = null;
        $data = [
            'name' => null,
            'description' => null
        ];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = intval($payload['projectId']);
            $taskId = $payload['taskId'];
            $state = $payload['state'];

            $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);

            if($task) {
                $project = $task->getTaskGroup()->getProject();

                if($projectId === $project->getId()) {
                    $auth = $authenticator->checkUserAuth($userId, $token, $project);
                    if(isset($auth['user'])) {
                        $task->setDone($state);
                        $task->setDoneBy($auth['user']);
                        $task->setDoneAt(new \DateTime('now'));

                        $entityManager->persist($task);
                        $entityManager->flush();
                        $success = true;
                    }
                }
            } else {
                $message = 'taskid_not_found';
            }


        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message,
            'task' => $data
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }

    /**
     * @Route("/task/done/get", name="api_task_done_get")
     */
    public function getDoneTasks(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $message = null;
        $tasks = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = intval($payload['projectId']);
            $groupId = $payload['groupId'];

            $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

            if($taskGroup) {
                $project = $taskGroup->getProject();
                if($projectId === $project->getId()) {
                    $auth = $authenticator->checkUserAuth($userId, $token, $project);
                    if(isset($auth['user'])) {
                        $tasks = $this->getDoctrine()->getRepository(Tasks::class)->getDoneTasks($taskGroup->getId());
                        $success = true;
                    } else {
                        $message = 'permission_denied';
                    }
                }
            } else {
                $message = 'groupid_not_found';
            }


        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message,
            'tasks' => $tasks
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }

    /**
     * @Route("/task/open/get", name="api_task_open_get")
     */
    public function getOpenTasks(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $message = null;
        $tasks = [];

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = intval($payload['projectId']);
            $groupId = $payload['groupId'];

            $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

            if($taskGroup) {
                $project = $taskGroup->getProject();
                if($projectId === $project->getId()) {
                    $auth = $authenticator->checkUserAuth($userId, $token, $project);
                    if(isset($auth['user'])) {
                        $tasks = $this->getDoctrine()->getRepository(Tasks::class)->getOpenTasks($groupId);
                        $success = true;
                    } else {
                        $message = 'permission_denied';
                    }
                }
            } else {
                $message = 'groupid_not_found';
            }


        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message,
            'tasks' => $tasks
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }


    private function increasePositions($groupId) {
        $this->getDoctrine()->getRepository(Tasks::class)->increasePositionsByOne($groupId);
    }


}