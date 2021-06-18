<?php
namespace Taskoo\Controller;

mb_http_output('UTF-8');

use Taskoo\Api\ApiController;
use Taskoo\Entity\Projects;
use Taskoo\Service\MailerService;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

class TestAPI extends ApiController
{
    /**
     * @Route("/sendTestMail", name="api_get_project_nav_test_data")
     */
    public function getProjectNavTestData(Request $request, Connection $connection, MailerService $mailerService)
    {

        $mailerService->sendTestInviteMail();
        return $this->responseManager->successResponse([], 'test');
    }
}