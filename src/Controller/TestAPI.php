<?php
namespace App\Controller;

mb_http_output('UTF-8');

use Doctrine\DBAL\Driver\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestAPI extends AbstractController
{

    /**
     * @Route("/api/getProjectTestData", name="api_get_project_test_data")
     */
    public function getProjectTestData(Request $request, Connection $connection)
    {
        $data = [];

        $data['project']['name'] = 'Zalando.de - Shoprelaunch 2023';
        $data['project']['deadline'] = '20.06.2022';

        $data['groups'][0] = [
            'id' => 12,
            'name' => 'backend',
            'tasks' => [
                0 => [
                    'name' => 'aufgabe1',
                    'id' => 1,
                    'dateDue' => null,
                    'user' => null
                ],
                1 => [
                    'name' => 'aufgabe2',
                    'id' => 2,
                    'dateDue' => null,
                    'user' => null
                ],
                2 => [
                    'name' => 'aufgabe3',
                    'id' => 3,
                    'dateDue' => '20.11.2020',
                    'user' => 1
                ],
                3 => [
                    'name' => 'aufgabe4',
                    'id' => 4,
                    'dateDue' => null,
                    'user' => null
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
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "GET");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
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
                    'id' => 4,
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
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "GET");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }
}