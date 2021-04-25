<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\Media;
use App\Entity\Notifications;
use App\Entity\TaskGroups;
use App\Entity\Tasks;
use App\Exception\InvalidRequestException;
use App\Exception\NotAuthorizedException;
use App\Service\TaskooFileService;
use Symfony\Component\Filesystem\Filesystem;
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
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);
        $entityManager = $this->getDoctrine()->getManager();


        if(isset($payload['mainTaskId'])) {
            $mainTaskId = $payload['mainTaskId'];
            $mainTask = $this->tasksRepository()->find($mainTaskId);
            /** @var TaskGroups $taskGroup */
            $taskGroup = $mainTask->getTaskGroup();

        } else {
            $groupId = $payload['groupId'];
            /** @var TaskGroups $taskGroup */
            $taskGroup = $this->taskGroupsRepository()->find($groupId);
        }

        if(!$taskGroup) throw new InvalidRequestException();
        $project = $this->authenticator->checkProjectPermission($auth, $taskGroup->getProject()->getId());
        $taskName = $payload['taskName'];

        if(isset($mainTask)) {
            $this->increaseSubPositions($mainTaskId);
        } else {
            $this->increasePositions($taskGroup->getId());
        }

        $task = new Tasks();
        $task->setName($taskName);
        $task->setPosition(0);
        $task->setDone(false);
        $task->setCreatedBy($auth->getUser());
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
    }

    /**
     * @Route("/task/{taskId}", name="api_task_update", methods={"PUT"})
     */
    public function updateTask(int $taskId, Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);
        /** @var $task Tasks */
        $task = $this->tasksRepository()->find($taskId);
        if(!$task) throw new InvalidRequestException();

        $project = $this->authenticator->checkProjectPermission($auth, $task->getTaskGroup()->getProject()->getId());

        $data = [];
        $entityManager = $this->getDoctrine()->getManager();

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

            if($project->getClosed()) {
                if(!$project->getProjectUsers()->contains($user)) throw new NotAuthorizedException();
            } else {
                if(!$project->getOrganisation()->getUsers()->contains($user)) throw new NotAuthorizedException();
            }

            $task->addAssignedUser($user);

            if($user->getId() !== $auth->getUser()->getId()) {
                $notification = new Notifications();
                $notification->setTask($task);
                $notification->setByUser($auth->getUser());
                $notification->setUser($user);
                $notification->setTime(new \DateTime('now'));
                $notification->setMessage('task_assigned');

                $entityManager->persist($notification);
            }

        }

        if(isset($payload['removeUser'])) {
            $user = $this->userRepository()->find($payload['removeUser']);

            $task->removeAssignedUser($user);
        }

        if(isset($payload['done'])) {
            $task->setDone($payload['done']);

            if($payload['done'] === true) {
                $task->setDoneBy($auth->getUser());
                $task->setDoneAt(new \DateTime('now'));

                $data['doneAt'] = new \DateTime('now');
                $data['doneBy'] = [
                    'firstname' => $auth->getUser()->getFirstname(),
                    'lastname' => $auth->getUser()->getLastname(),
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

    /**
     * @Route("/task/{taskId}", name="api_task_load", methods={"GET"})
     */
    public function getTask(int $taskId, Request $request)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);
        //get Task
        /** @var $task Tasks */
        $task = $this->tasksRepository()->find($taskId);
        if(!$task) throw new InvalidRequestException();

        $project = $this->authenticator->checkProjectPermission($auth, $task->getTaskGroup()->getProject()->getId());

        $getSubTasks = $request->query->get('subTasks');

        //collect data for app
        $data['task']['id'] = $taskId;
        $data['task']['name'] = $task->getName();
        $data['task']['description'] = $task->getDescription();
        $data['task']['dateDue'] = $task->getDateDue();
        $data['task']['isDone'] = $task->getDone();
        $data['task']['subTasks'] = null;
        $data['task']['project']['name'] = $project->getName();
        $data['task']['project']['id'] = $project->getId();

        //project organisation data
        if($project->getOrganisation()) {
            $data['task']['project']['organisation']['id'] = $project->getOrganisation()->getId();
            $data['task']['project']['organisation']['name'] = $project->getOrganisation()->getName();
            if($project->getOrganisation()->getColor()) {
                $data['task']['project']['organisation']['color'] = $project->getOrganisation()->getColor()->getHexCode();
            }
        }

        //task priority
        $data['task']['highPriority'] = $task->getHigherPriority();

        //mainTask data
        $mainTask = $task->getMainTask();
        if($mainTask) {
            $data['task']['mainTaskId'] = $mainTask->getId();
            $data['task']['mainTask'] = $mainTask->getName();
        }

        //assignable users for task
        if($project->getClosed()) {
            $data['task']['availableUsers'] = $this->projectsRepository()->getProjectUsers($project->getId());
        } else {
            $data['task']['availableUsers'] = $this->organisationsRepository()->getOrganisationUsers($project->getOrganisation()->getId());
        }

        //task finished data
        if($task->getDone() === true) {
            $data['task']['doneBy'] = [
                'firstname' => $task->getDoneBy()->getFirstname(),
                'lastname' => $task->getDoneBy()->getLastname(),
                'id' => $task->getDoneBy()->getId()
            ];

            $data['task']['doneAt'] = $task->getDoneAt();
        }

        //subTasks
        if($getSubTasks === 'true') {
            $data['task']['subTasks'] = $this->tasksRepository()->getSubTasks($task->getId());

            foreach($data['task']['subTasks'] as &$subTask) {
                if($subTask['description']) {
                    $subTask['description'] = true;
                }

                if($this->mediaRepository()->findOneBy(['task' => $subTask['id']])) {
                    $subTask['hasFiles'] = true;
                }
            }
        }

        //assigned users
        $data['task']['users'] = $this->tasksRepository()->getAssignedUsers($task->getId());

        //task media
        $data['task']['files'] = [];
        if($task->getMedia()) {
            $files = $task->getMedia();

            foreach($files as $file) {
                $data['task']['files'][] = [
                    'fileName' => $file->getFileName(),
                    'fileSize' => $file->getFileSize(),
                    'fileExtension' => $file->getExtension(),
                    'filePath' => $file->getFilePath(),
                    'id' => $file->getId()
                ];
            }
        }

        return $this->responseManager->successResponse($data, 'task_loaded');
    }

    /**
     * @Route("/task/{taskId}", name="api_task_delete", methods={"DELETE"})
     * @param int $taskId
     * @param Request $request
     * @param TaskooFileService $fileService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteTask(int $taskId, Request $request, TaskooFileService $fileService)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);

        //get Task
        /** @var Tasks $task */
        $task = $this->tasksRepository()->find($taskId);
        if(!$task) throw new InvalidRequestException();
        $project = $this->authenticator->checkProjectPermission($auth, $task->getTaskGroup()->getProject()->getId());

        //delete files uploaded to task
        $fileSystem = new Filesystem();
        $taskMedia = $fileService->getTargetDirectory().'/'.$task->getId();
        if($fileSystem->exists($taskMedia)) $fileSystem->remove($taskMedia);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($task);
        $entityManager->flush();
        return $this->responseManager->successResponse($data, 'task_deleted');
    }

    /**
     * @Route("/task/{taskId}/file", name="api_task_add_file", methods={"POST"})
     * @param int $taskId
     * @param Request $request
     * @param TaskooFileService $fileService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addFileToTask(int $taskId, Request $request, TaskooFileService $fileService)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);
        //get Task
        /** @var $task Tasks */
        $task = $this->tasksRepository()->find($taskId);
        if(!$task) throw new InvalidRequestException();

        $project = $this->authenticator->checkProjectPermission($auth, $task->getTaskGroup()->getProject()->getId());

        $uploadedFile = $request->files->get('file');

        $fileService->upload($uploadedFile, $auth->getUser(), $task);

        $task = $this->tasksRepository()->find($taskId);

        if($task->getMedia()) {
            $files = $task->getMedia();

            foreach($files as $file) {
                $data['files'][] = [
                    'fileName' => $file->getFileName(),
                    'fileSize' => $file->getFileSize(),
                    'fileExtension' => $file->getExtension(),
                    'filePath' => $file->getFilePath(),
                    'id' => $file->getId()
                ];
            }
        }

        return $this->responseManager->successResponse($data, 'file_uploaded');
    }

    private function increaseSubPositions($mainTaskId) {
        $this->getDoctrine()->getRepository(Tasks::class)->increaseSubPositionsByOne($mainTaskId);
    }

    private function increasePositions($groupId) {
        $this->getDoctrine()->getRepository(Tasks::class)->increasePositionsByOne($groupId);
    }


}