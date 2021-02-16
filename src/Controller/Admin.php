<?php
namespace App\Controller;

mb_http_output('UTF-8');
date_default_timezone_set('Europe/Amsterdam');

use App\Api\TaskooApiController;
use App\Service\TaskooMailerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;

class Admin extends TaskooApiController
{
    /**
     * @Route("/test/sendMailaa", name="api_test_sendmail", methods={"GET"})
     */
    public function getUserNotifications(Request $request, TaskooMailerService $mailService)
    {
        $data = [];

        $mailService->sendMail();


        return $this->responseManager->successResponse($data, 'sendMail');
    }
}