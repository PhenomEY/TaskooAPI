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

        if(!empty($payload)) {
            $projectId = $payload['projectId'];
            $groupName = $payload['name'];
            $position = $payload['position'];
            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);

            if($project) {
                $auth = $authenticator->checkUserAuth($userId, $token, $project);

                if($auth) {
                    $taskGroup = new TaskGroups();
                    $taskGroup->setName($groupName);
                    $taskGroup->setProject($project);
                    $taskGroup->setPosition($position);
                    $taskGroup->setCreatedAt(new \DateTime('now'));

                    $entityManager->persist($taskGroup);

                    $project->addTaskGroup($taskGroup);

                    $entityManager->persist($project);
                    $entityManager->flush();

                    $createdId = $taskGroup->getId();
                    $success = true;
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
     * @Route("/taskgroup/changeName", name="api_taskgroup_changename")
     */
    public function changeName(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $groupId = $payload['groupId'];
            $projectId = $payload['projectId'];
            $newName = $payload['newName'];

            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);
            $auth = $authenticator->checkUserAuth($userId, $token, $project);

            if($auth) {
                $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($groupId);

                if($taskGroup) {
                    $taskGroup->setName($newName);
                    $entityManager->persist($taskGroup);
                    $entityManager->flush();
                    $success = true;
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

    /**
     * @Route("/taskgroup/changePositions", name="api_taskgroup_changepositions")
     */
    public function changePositions(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        if(!empty($payload)) {
            $projectId = $payload['projectId'];
            $positions = $payload['positions'];

            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);
            $auth = $authenticator->checkUserAuth($userId, $token, $project);

            if($auth) {
              foreach($positions as $position=>$id) {
                  $taskGroup = $this->getDoctrine()->getRepository(TaskGroups::class)->find($id);
                  $taskGroup->setPosition($position);
                  $entityManager->persist($taskGroup);
              }

              $entityManager->flush();
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