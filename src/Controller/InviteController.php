<?php
namespace Taskoo\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use Taskoo\Api\ApiController;
use Taskoo\Entity\TempUrls;
use Taskoo\Entity\User;
use Taskoo\Entity\UserPermissions;
use Taskoo\Exception\InvalidRequestException;
use Taskoo\Service\InviteService;
use Taskoo\Service\MailerService;
use Taskoo\Service\TemporaryURLService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class InviteController extends ApiController
{
    /**
     * @Route("/invite/{inviteId}", name="api_user_get_invite", methods={"GET"})
     * @param $inviteId
     * @param Request $request
     * @param TemporaryURLService $temporaryURLService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getInvite($inviteId, InviteService $inviteService)
    {
        $invite = $inviteService->load($inviteId);

        $data['user'] = [
            'firstname' => $invite->getUser()->getFirstname(),
            'lastname' => $invite->getUser()->getLastname(),
            'email' => $invite->getUser()->getEmail()
        ];

        return $this->responseManager->successResponse($data, 'invite_valid');
    }

    /**
     * @Route("/invite/{inviteId}", name="api_user_finish_invite", methods={"POST"})
     * @param $inviteId
     * @param Request $request
     * @param TemporaryURLService $temporaryURLService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function finishInvite($inviteId, Request $request, InviteService $inviteService)
    {
        $payload = $request->toArray();

        $inviteService->finish($inviteId, $payload['password']);

        return $this->responseManager->successResponse([], 'invite_finished');
    }

    /**
     * @Route("/invite", name="api_user_create_invite", methods={"POST"})
     * @param Request $request
     * @param TemporaryURLService $temporaryURLService
     * @param MailerService $mailerService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createInvite(Request $request, InviteService $inviteService)
    {
        $userData = $request->toArray();
        if(!$userData) throw new InvalidRequestException();
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);

        $inviteService->create($userData);

        return $this->responseManager->successResponse([], 'user_invite_send');
    }
}
