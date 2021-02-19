<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Entity\TempUrls;
use App\Entity\User;
use App\Service\TaskooMailerService;
use App\Service\TemporaryURLService;
use phpDocumentor\Reflection\Types\Integer;
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
            $data['user'] = [
                'firstname' => $invite->getUser()->getFirstname(),
                'lastname' => $invite->getUser()->getLastname(),
                'email' => $invite->getUser()->getEmail()
            ];

            return $this->responseManager->successResponse($data, 'invite_valid');
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/invite/{inviteId}", name="api_user_finish_invite", methods={"POST"})
     * @param $inviteId
     * @param Request $request
     * @param TemporaryURLService $temporaryURLService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function finishInvite($inviteId, Request $request, TemporaryURLService $temporaryURLService)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);

        /**
         * @var $invite TempUrls
         */
        $invite = $temporaryURLService->verifyURL($inviteId, $temporaryURLService::INVITE_ACTION);

        if($invite && isset($payload['password'])) {
            $user = $invite->getUser();

            $hashedPassword = $this->authenticator->generatePassword($payload['password']);

            $user->setPassword($hashedPassword);
            $user->setActive(true);

            $entityManager = $this->getDoctrine()->getManager();
            $deleteManager = clone $entityManager;

            //set user
            $entityManager->persist($user);
            $entityManager->flush();

            //delete invite URL
            $deleteManager->remove($invite);
            $deleteManager->flush();


            return $this->responseManager->successResponse($data, 'invite_finished');
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/invite", name="api_user_create_invite", methods={"POST"})
     * @param Request $request
     * @param TemporaryURLService $temporaryURLService
     * @param TaskooMailerService $mailerService
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createInvite(Request $request, TemporaryURLService $temporaryURLService, TaskooMailerService $mailerService)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        $token = $request->headers->get('authorization');

        //check if auth data got sent
        if(isset($token) && isset($payload)) {
            $auth = $this->authenticator->checkUserAuth($token, null,  10);

            if(isset($auth['user'])) {
                //check if email is valid
                if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                    return $this->responseManager->errorResponse('invalid_email');
                }

                //check if send email is already in use
                $emailInUse = $this->userRepository()->findOneBy([
                    'email' => $payload['email']
                ]);

                //if mail already exists return error
                if($emailInUse) {
                    return $this->responseManager->errorResponse('already_registered');
                }

                //else create new user
                $user = new User();
                $user->setEmail($payload['email']);
                $user->setFirstname($payload['firstname']);
                $user->setLastname($payload['lastname']);
                $user->setRole(1);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $inviteURL = $temporaryURLService->generateURL($temporaryURLService::INVITE_ACTION, 24, $user);

                $mailerService->sendInviteMail($inviteURL, 24);

                return $this->responseManager->successResponse($data, 'user_invite_send');
            }
        }

//        $temporaryURLService->generateURL($temporaryURLService::INVITE_ACTION, 24);

        return $this->responseManager->forbiddenResponse();
    }
}