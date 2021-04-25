<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Api\TaskooResponseManager;
use App\Entity\Media;
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

        $mediaPath = $fileService->getTargetDirectory();
        $filePath = $media->getFilePath();

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($media);
        $entityManager->flush();

        $fileSystem = new Filesystem();
        $fileSystem->remove($mediaPath.'/'.$filePath);

        return $this->responseManager->successResponse($data, 'file_deleted');
    }

}