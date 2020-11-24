<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Entity\Projects;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TestAPI extends AbstractController
{
    /**
     * @Route("/api/getProjectNavTestData", name="api_get_project_nav_test_data")
     */
    public function getProjectNavTestData(Request $request, Connection $connection)
    {
        $data = [];
        $data['projects'] = [];

        $projects = $this->getDoctrine()->getRepository(Projects::class)->findAll();



        foreach($projects as $project) {
            array_push($data['projects'], [
                'name' => $project->getName(),
                'id' => $project->getId()
            ]);

        }

//        $data['projects'] = [
//                0 => [
//                    'name' => 'TestProject',
//                    'id' => 4,
//                ],
//                1 => [
//                    'name' => 'FIFA 22',
//                    'id' => 2,
//                ],
//        ];

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