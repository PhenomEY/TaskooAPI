<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\TaskGroups;
use App\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TaskGroup extends TaskooApiController
{

    /**
     * @Route("/taskgroup", name="api_taskgroup_add", methods={"POST"})
     */
    public function addTaskGroup(Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $auth = $request->attributes->get('auth');

        $projectId = $payload['projectId'];
        $project = $this->authenticator->checkProjectPermission($auth, $projectId);

        $entityManager = $this->getDoctrine()->getManager();

        $groupName = $payload['name'];
        $position = $payload['position'];

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
        return $this->responseManager->createdResponse($data, 'group_created');
    }


    /**
     * @Route("/taskgroup/{groupId}", name="api_taskgroup_update", methods={"PUT"})
     */
    public function updateTaskgroup(int $groupId, Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $auth = $request->attributes->get('auth');
        $taskGroup = $this->taskGroupsRepository()->find($groupId);
        if(!$taskGroup) throw new InvalidRequestException();
        $project = $this->authenticator->checkProjectPermission($auth, $taskGroup->getProject()->getId());

        $entityManager = $this->getDoctrine()->getManager();

        if(isset($payload['name'])) {
            $taskGroup->setName($payload['name']);
        }

        if(isset($payload['taskPositions'])) {
            $positions = $payload['taskPositions'];
            foreach($positions as $position=>$id) {
                $task = $this->tasksRepository()->find($id);

                //if task got moved into this group from another
                if($task->getTaskGroup() !== $taskGroup) {
                    $task->setTaskGroup($taskGroup);
                }

                $task->setPosition($position);
                $entityManager->persist($task);
            }
        }

        $entityManager->persist($taskGroup);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'taskgroup_updated');
    }

    /**
     * @Route("/taskgroup/{groupId}", name="api_taskgroup_delete", methods={"DELETE"})
     * @param int $groupId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteTaskgroup(int $groupId, Request $request)
    {
        $data = [];

        $auth = $request->attributes->get('auth');
        /** @var TaskGroups $taskGroup */
        $taskGroup = $this->taskGroupsRepository()->find($groupId);
        if(!$taskGroup) throw new InvalidRequestException();
        $project = $this->authenticator->checkProjectPermission($auth, $taskGroup->getProject()->getId());

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($taskGroup);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'taskgroup_deleted');
    }


    /**
     * @Route("/taskgroup/{groupId}", name="api_taskgroup_get", methods={"GET"})
     */
    public function getTaskgroup(int $groupId, Request $request)
    {
        $data = [];
        $auth = $this->authenticator->verifyToken($request);
        /** @var TaskGroups $taskGroup */
        $taskGroup = $this->taskGroupsRepository()->find($groupId);
        if(!$taskGroup) throw new InvalidRequestException();

        $project = $this->authenticator->checkProjectPermission($auth, $taskGroup->getProject()->getId());

        $doneTasks = $request->query->get('done');

        if($doneTasks === 'true') {
            $data['tasks'] = $this->tasksRepository()->getDoneTasks($groupId);

        } elseif ($doneTasks === 'false') {
            $tasks = $this->tasksRepository()->getOpenTasks($groupId);

            foreach($tasks as &$task) {
                if($task['description']) {
                    $task['description'] = true;
                }

                $subTasks = $this->tasksRepository()->getSubTasks($task['id']);
                if($subTasks) {
                    $task['subTasks'] = true;
                }

                $users = $this->tasksRepository()->getAssignedUsers($task['id']);
                if($users) {
                    $task['user'] = $users[0];
                }
            }

            $data['tasks'] = $tasks;
        }

        return $this->responseManager->successResponse($data, 'taskgroup_loaded');

    }
}