<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Entity\Projects;
use App\Service\TaskooMailerService;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;

class TestAPI extends TaskooApiController
{
    /**
     * @Route("/sendTestMail", name="api_get_project_nav_test_data")
     */
    public function getProjectNavTestData(Request $request, Connection $connection, TaskooMailerService $mailerService)
    {

        $mailerService->sendTestInviteMail();
        return $this->responseManager->successResponse([], 'test');
    }
}