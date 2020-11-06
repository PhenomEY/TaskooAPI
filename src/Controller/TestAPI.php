<?php
namespace App\Controller;

mb_http_output('UTF-8');

use Doctrine\DBAL\Driver\Connection;
use mysqli;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Json;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use DateTime;


class TestAPI extends AbstractController
{

    /**
     * @Route("/api/getProjectTestData", name="api_get_project_test_data")
     */
    public function getProjectTestData(Request $request, Connection $connection)
    {
        $data = [];

        $data['groups'][0] = [
            'id' => 12,
            'name' => 'backend',
            'tasks' => [
                0 => [
                    'name' => 'aufgabe1',
                    'id' => 1,
                ],
                1 => [
                    'name' => 'aufgabe2',
                    'id' => 2,
                ],
                2 => [
                    'name' => 'aufgabe3',
                    'id' => 3,
                ],
                3 => [
                    'name' => 'aufgabe4',
                    'id' => 4,
                ],
            ]
        ];


        $data['groups'][1] = [

            'id' => 24,
            'name' => 'frontend',
            'tasks' => [
                0 => [
                    'name' => 'Caufgabe1',
                    'id' => 1,
                ],
                1 => [
                    'name' => 'Caufgabe2',
                    'id' => 2,
                ],
                2 => [
                    'name' => 'Caufgabe3',
                    'id' => 3,
                ],
                3 => [
                    'name' => 'Caufgabe4',
                    'id' => 4,
                ],
            ]
        ];

        $response = new JsonResponse([
            'success' => true,
            'data' => $data
        ]);
        return $response;
    }


    /**
     * @Route("/api/getProjectNavTestData", name="api_get_project_nav_test_data")
     */
    public function getProjectNavTestData(Request $request, Connection $connection)
    {
        $data = [];

        $data['projects'] = [
                0 => [
                    'name' => 'TestProject',
                    'id' => 1,
                ],
                1 => [
                    'name' => 'FIFA 22',
                    'id' => 2,
                ],
        ];

        $response = new JsonResponse([
            'success' => true,
            'data' => $data
        ]);
        return $response;
    }
}