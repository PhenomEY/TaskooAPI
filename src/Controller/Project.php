<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Entity\Projects;
use App\Entity\TaskGroups;
use App\Entity\Tasks;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserAuth;

class Project extends AbstractController
{


    /**
     * @Route("/project/load", name="api_project_load")
     */
    public function getProject(Request $request)
    {
        $success = false;
        $message = 'login_failed';

        $entityManager = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);

        //if payload exists
        if (!empty($payload)) {
            $projectId = $payload['projectId'];


            //load project by id
            $project = $this->getDoctrine()->getRepository(Projects::class)->find($projectId);
            print_r($project);
        }

        $response = new JsonResponse([
            'success' => $success,
            'message' => $message
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "POST");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }
}