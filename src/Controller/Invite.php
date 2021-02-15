<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\TempUrls;
use App\Service\TemporaryURLService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Invite extends TaskooApiController
{

    /**
     * @Route("/invite/{inviteId}", name="api_user_get_invite", methods={"GET"})
     * @param $inviteId
     * @param Request $request
     * @param TemporaryURLService $temporaryURLService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getInvite($inviteId, Request $request, TemporaryURLService $temporaryURLService)
    {
        $data = [
            'inviteId' => $inviteId
        ];

        /**
         * @var $invite TempUrls
         */
        $invite = $temporaryURLService->verifyURL($inviteId, $temporaryURLService::INVITE_ACTION);

        if($invite) {
            $data['user']['firstname'] = $invite->getUser()->getFirstname();
            $data['user']['lastname'] = $invite->getUser()->getLastname();

            return $this->responseManager->successResponse($data, 'invite_valid');
        }

//        $temporaryURLService->generateURL($temporaryURLService::INVITE_ACTION, 24);

        return $this->responseManager->forbiddenResponse();
    }
}