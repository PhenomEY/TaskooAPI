<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Api\TaskooResponseManager;
use App\Entity\Media;
use App\Entity\Tasks;
use App\Exception\InvalidRequestException;
use App\Exception\NotAuthorizedException;
use App\Security\TaskooAuthenticator;
use App\Service\TaskooColorService;
use App\Service\TaskooFileService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class Files extends TaskooApiController
{

    const IS_PUBLIC = true;

    /**
     * @Route("/file/{filePath}", name="api_file_get", methods={"GET"})
     */
    public function getFile(String $filePath, Request $request, TaskooFileService $fileService)
    {
        /** @var Media $media */
        $media = $this->mediaRepository()->findOneBy(['filePath' => $filePath]);
        if(!$media) throw new NotFoundHttpException();

        $mediaPath = $fileService->getTargetDirectory();
        $file = file_get_contents($mediaPath.'/'.$filePath);

        $response = new Response($file);
        $response->headers->set('Content-Type', $media->getMimeType());
        $response->headers->set('Content-Disposition', 'inline; filename="'.$media->getFileName().'"');
        $response->setMaxAge(0);

        return $response;
    }


    /**
     * @Route("/file/{fileId}", name="api_file_get", methods={"DELETE"})
     * @param int $fileId
     * @param Request $request
     * @param TaskooFileService $fileService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteFile(int $fileId, Request $request, TaskooFileService $fileService)
    {
        $data = [];

        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);

        /** @var Media $media */
        $media = $this->mediaRepository()->find($fileId);
        if(!$media) throw new NotFoundHttpException();

        if($media->getTask()) $this->authenticator->checkProjectPermission($auth, $media->getTask()->getTaskGroup()->getProject()->getId());

        $fileService->delete($media);

        return $this->responseManager->successResponse($data, 'file_deleted');
    }

    /**
     * @Route("/file", name="api_file_upload", methods={"POST"})
     * @param Request $request
     * @param TaskooFileService $fileService
     */
    public function uploadFile(Request $request, TaskooFileService $fileService)
    {
        $data = [];
        $taskId = $request->get('taskId');
        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);
        $task = null;

        //get Task
        if($taskId) {
            /** @var $task Tasks */
            $task = $this->tasksRepository()->find($taskId);
            if(!$task) throw new InvalidRequestException();

            //check auth
            $this->authenticator->checkProjectPermission($auth, $task->getTaskGroup()->getProject()->getId());
        }

        $uploadedFile = $request->files->get('file');
        $media = $fileService->upload($uploadedFile, $auth->getUser(), $task);

        $data = ($task && $task->getMedia()) ? $this->getTaskData($taskId, $media) : $this->getAvatarData($media);

        return $this->responseManager->successResponse($data, 'file_uploaded');
    }

    private function getTaskData(int $taskId, Media $media): ?array {
        $task = $this->tasksRepository()->find($taskId);
        $files = $task->getMedia();
        $data = [];

        foreach($files as $file) {
            $data['files'][] = [
                'fileName' => $file->getFileName(),
                'fileSize' => $file->getFileSize(),
                'fileExtension' => $file->getExtension(),
                'filePath' => $file->getFilePath(),
                'id' => $file->getId()
            ];
        }

        return $data;
    }

    private function getAvatarData(Media $media): ?array {
        $data = [];
        $data['avatar'] = [
            'id' => $media->getId(),
            'filePath' => $media->getFilePath(),
            'fileExtension' => $media->getExtension()
        ];

        return $data;
    }
}