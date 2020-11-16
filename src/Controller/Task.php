<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Entity\Organisations;
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

        $auth = $authenticator->checkUserAuth($userId, $token);

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload) && $auth) {
            $taskData = $payload['model'][0];
            $taskGroupId = $payload['groupId'];

            $task = new Tasks();
            $task->setName($taskData['name']);
            $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($taskGroupId);
            $task->setTaskGroup($taskGroup);
            $task->setCreatedAt(new \DateTimeImmutable('now'));
            $task->setCreatedBy($auth);

            $entityManager->persist($task);
            $entityManager->flush();

            $createdId = $task->getId();
            $sortedTasks = $payload['model'];
            $sortedTasks[0]['id'] = $createdId;

            print_r($sortedTasks);

            //Set sorted tasks for taskgroup
            $taskGroup->setTasks($sortedTasks);

            //flush taskgroup
            $entityManager->persist($taskGroup);
            $entityManager->flush();
            $success = true;
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
}