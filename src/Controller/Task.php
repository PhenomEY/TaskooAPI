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

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');



        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = $payload['projectId'];
            $groupId = $payload['groupId'];
            $taskName = $payload['taskName'];
            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);

            if($project) {
                $auth = $authenticator->checkUserAuth($userId, $token, $project);

                if($auth) {
                    $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

                    if($taskGroup) {
                        $this->increasePositions($taskGroup->getId());

                        $task = new Tasks();
                        $task->setName($taskName);
                        $task->setPosition(0);
                        $entityManager->persist($task);

                        $taskGroup->addTask($task);
                        $entityManager->persist($taskGroup);
                        $entityManager->flush();

                        $createdId = $task->getId();
                        $success = true;
                    }
                }
            }
        }

        $response = new JsonResponse([
            'success' => $success,
            'createdId' => $createdId
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

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = $payload['projectId'];
            $taskId = $payload['taskId'];
            $newName = $payload['newName'];
            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);

            if($project) {
                $auth = $authenticator->checkUserAuth($userId, $token, $project);

                if($auth) {
                    $task = $this->getDoctrine()->getRepository(Tasks::class)->find($taskId);

                    if($task) {
                        $task->setName($newName);
                        $entityManager->persist($task);
                        $entityManager->flush();
                        $success = true;
                    }
                }
            }
        }

        $response = new JsonResponse([
            'success' => $success
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