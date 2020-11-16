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
use App\Entity\User;
use App\Entity\UserAuth;

class Project extends AbstractController
{


    /**
     * @Route("/project/load", name="api_project_load")
     */
    public function getProject(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $projectName = null;
        $deadline = null;
        $projectUser = null;
        $taskGroups = null;

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $auth = $authenticator->checkUserAuth($userId, $token);

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

//        //if payload exists
//        if (!empty($payload) && $auth) {
//            $projectId = $payload['projectId'];
        $projectId = 2;


            //load project by id
            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);

            if($project !== null) {
                $projectName = $project->getName();
                $deadline = $project->getDeadline()->format('d.m.Y');
                $taskGroups = $project->getTaskgroups()
                                ->map(function($group) {
                                    $tasks = null;

                                    if(!$group->getTasks()->isEmpty()) {
                                        $tasks = $group->getTasks()->map(function($task) {
                                            return $task->getName();
                                        });
                                    }

                                return [
                                  'name' => $group->getName(),
                                  'id' => $group->getId(),
                                   'tasks' => $tasks
                                ];
                                })->toArray();

                $success = true;
            }

//        }

        $response = new JsonResponse([
            'success' => $success,
            'project' => [
                'name' => $projectName,
                'deadline' => $deadline
            ],
            'groups' => $taskGroups
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }

    /**
     * @Route("/project/create", name="api_project_create")
     */
    public function createProject(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $message = 'login_failed';
        $projectId = null;

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        $token = $request->headers->get('authorization');
        $userId = $request->headers->get('user');

        $auth = $authenticator->checkUserAuth($userId, $token, 10);

        //if payload exists
        if (!empty($payload) && $auth) {
            $projectName = $payload['projectName'];
            $deadline = $payload['deadline'];
            $organisationId = 1;
            $user = null;

            //Create new Project
            $project = new Projects();
            $project->setName($projectName);
            $dateTime = new \DateTime($deadline);
            $project->setDeadline($dateTime);
            $project->setCreatedAt(new \DateTime('now'));
            $entityManager->persist($project);
            $entityManager->flush();

            //Create default Group
            $taskGroup = new TaskGroups();
            $taskGroup->setCreatedAt(new \DateTime('now'));
            $taskGroup->setProject($project);
            $taskGroup->setName('Neue Gruppe');
            $taskGroup->setPosition(0);
            $entityManager->persist($taskGroup);
            $entityManager->flush();

            $project->addTaskGroup($taskGroup);
            $entityManager->persist($project);
            $entityManager->flush();



            $projectId = $project->getId();
            $success = true;
            $message = 'project_created';
        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message,
            'projectId' => $projectId
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }
}