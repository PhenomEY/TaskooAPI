<?php
namespace Taskoo\Controller;

use Taskoo\Api\ApiController;
use Taskoo\Service\InviteService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/invite", name="invite_")
 */
class InviteController extends ApiController
{
    /**
     * @Route("/{inviteId}", name="load", methods={"GET"})
     * @param $inviteId
     * @param InviteService $inviteService
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
     * @Route("/{inviteId}", name="finish", methods={"POST"})
     * @param $inviteId
     * @param Request $request
     * @param InviteService $inviteService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function finishInvite($inviteId, Request $request, InviteService $inviteService)
    {
        $payload = $request->toArray();

        $inviteService->finish($inviteId, $payload['password']);

        return $this->responseManager->successResponse([], 'invite_finished');
    }

    /**
     * @Route("", name="create", methods={"POST"})
     * @param Request $request
     * @param InviteService $inviteService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createInvite(Request $request, InviteService $inviteService)
    {
        $userData = $request->toArray();
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);

        $inviteService->create($userData);

        return $this->responseManager->successResponse([], 'user_invite_send');
    }
}
