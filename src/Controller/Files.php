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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class Files extends TaskooApiController
{
    /**
     * @Route("/file/{fileId}", name="api_file_get", methods={"GET"})
     */
    public function getFile(int $fileId, Request $request, TaskooFileService $fileService)
    {

        $mediaToken = $request->query->get('mediaToken');

        if($mediaToken !== 'media') {
            throw new NotAuthorizedException();
        }
        /** @var Media $media */
        $media = $this->mediaRepository()->find($fileId);
        if(!$media) throw new InvalidRequestException();

        $mediaPath = $fileService->getTargetDirectory();

        $file = file_get_contents($mediaPath.'/'.$media->getFileName());

        $response = new Response($file);
        $response->headers->set('Content-Type', $media->getMimeType());
        $response->setMaxAge(0);

        return $response;
    }

}