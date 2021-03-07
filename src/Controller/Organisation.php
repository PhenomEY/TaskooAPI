<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Entity\Organisations;
use App\Entity\Projects;
use App\Security\TaskooAuthenticator;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Organisation extends TaskooApiController
{
    /**
     * @Route("/organisation", name="api_organisation_get", methods={"GET"})
     */
    public function getOrganisations(Request $request)
    {
        $data = [];
        $token = $request->headers->get('authorization');
        $entityManager = $this->getDoctrine()->getManager();

        //check if auth token got sent
        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token, null, 10);

            if(isset($auth['user'])) {
                $organisations = $this->organisationsRepository()->findAll();

                foreach($organisations as $key=>$organisation) {
                    $data['organisations'][$key] = [
                        'id' => $organisation->getId(),
                        'name' => $organisation->getName(),
                        'color' => $organisation->getColor()->getHexCode()
                    ];
                }

                return $this->responseManager->successResponse($data, 'organisations_loaded');
            }
        }

        return $this->responseManager->forbiddenResponse();
    }

    /**
     * @Route("/organisation/{orgId}/projects", name="api_organisation_get_projects", methods={"GET"})
     */
    public function getOrganisationProjects(int $orgId, Request $request)
    {
        $data = [
            'projects' => []
        ];
        $token = $request->headers->get('authorization');
        $entityManager = $this->getDoctrine()->getManager();

        //check if auth token got sent
        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token);

            if(isset($auth['user'])) {
                /**
                 * @var $organisation Organisations
                 */
                $organisation = $this->organisationsRepository()->find($orgId);

                if($organisation) {
                    $projects = $organisation->getProjects();

                    foreach($projects as $project) {
                        $projectData = [];
                        if($project->getClosed() && $auth['user']->getRole() !== $this->authenticator::IS_ADMIN) {

                            if($project->getProjectUsers()->contains($auth['user'])) {
                                $projectData = [
                                    'name' => $project->getName(),
                                    'id' => $project->getId(),
                                    'closed' => $project->getClosed()
                                ];
                                array_push($data, $projectData);
                            } else {
                                continue;
                            }
                        } else {
                            $projectData = [
                                'name' => $project->getName(),
                                'id' => $project->getId(),
                                'closed' => $project->getClosed()
                            ];

                            array_push($data['projects'], $projectData);
                        }
                    }

                    return $this->responseManager->successResponse($data, 'projects_loaded');
                }
            }
        }

        return $this->responseManager->forbiddenResponse();
    }
}