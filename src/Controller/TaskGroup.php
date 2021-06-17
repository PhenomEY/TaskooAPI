<?php
namespace Taskoo\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use Taskoo\Api\TaskooApiController;
use Taskoo\Entity\TaskGroups;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Service\TaskGroupService;
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

        $auth = $this->authenticator->verifyToken($request);

        $projectId = $payload['projectId'];
        $project = $this->authenticator->checkProjectPermission($auth, $projectId);

        $entityManager = $this->getDoctrine()->getManager();

        $groupName = $payload['name'];
        $position = $payload['position'];

        $taskGroup = new TaskGroups();
        $taskGroup->setName($groupName);
        $taskGroup->setProject($project);
        $taskGroup->setPosition($position);

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

        $auth = $this->authenticator->verifyToken($request);
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

        $auth = $this->authenticator->verifyToken($request);
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
    public function getTaskgroup(int $groupId, Request $request, TaskGroupService $taskGroupService)
    {
        $data = [];
        $auth = $this->authenticator->verifyToken($request);
        /** @var TaskGroups $taskGroup */
        $taskGroup = $this->taskGroupsRepository()->find($groupId);
        if(!$taskGroup) throw new InvalidRequestException();

        $project = $this->authenticator->checkProjectPermission($auth, $taskGroup->getProject()->getId());

        $doneTasks = $request->query->get('done');

        if($doneTasks === 'true') {
            $data['tasks'] = $taskGroupService->loadTasks($taskGroup, true);

        } elseif ($doneTasks === 'false') {
            $data['tasks'] = $taskGroupService->loadTasks($taskGroup, false);
        }

        return $this->responseManager->successResponse($data, 'taskgroup_loaded');

    }
}