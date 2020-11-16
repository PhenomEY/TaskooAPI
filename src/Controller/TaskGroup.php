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

class TaskGroup extends AbstractController
{


    /**
     * @Route("/taskgroup/add", name="api_taskgroup_add")
     */
    public function addTaskGroup(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $createdId = null;

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $auth = $authenticator->checkUserAuth($userId, $token);

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload) && $auth) {
            $model = $payload['model'];
            $addedGroupKey = array_key_last($model);
            $projectId = $payload['projectId'];

            $newGroup = new TaskGroups();
            $newGroup->setName($model[$addedGroupKey]['name']);
            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);
            $newGroup->setProject($project);

            //create new group in db
            $entityManager->persist($newGroup);
            $entityManager->flush();

            $createdId = $newGroup->getId();

            //set flushed group id into model
            $model[$addedGroupKey]['id'] = $createdId;

            $project->setTaskgroups($model);
            //save new groups to project
            $entityManager->persist($project);
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


    /**
     * @Route("/taskgroup/changeName", name="api_taskgroup_changename")
     */
    public function changeName(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $auth = $authenticator->checkUserAuth($userId, $token);

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload) && $auth) {
            $groupKey = $payload['groupKey'];
            $projectId = $payload['projectId'];
            $newName = $payload['newName'];

            $changeGroupName = $this->getDoctrine()->getRepository(Projects::class)->changeGroupName($groupKey, $newName, $projectId);

            if($changeGroupName !== false) {
                $success = true;
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
}