<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Entity\Organisations;
use App\Exception\InvalidRequestException;
use App\Service\TaskooColorService;
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

        $auth = $this->authenticator->verifyToken($token, 'ADMINISTRATION');

        $organisations = $this->organisationsRepository()->findAll();

        foreach($organisations as $key=>$organisation) {
            $data['organisations'][$key] = [
                'id' => $organisation->getId(),
                'name' => $organisation->getName(),
                'projectCount' => $organisation->getProjects()->count(),
                'userCount' => $organisation->getUsers()->count()
            ];

            if($organisation->getColor()) {
                $data['organisations'][$key]['color'] = [
                    'id' => $organisation->getColor()->getId(),
                    'hexCode' => $organisation->getColor()->getHexCode()
                ];
            }
        }

        return $this->responseManager->successResponse($data, 'organisations_loaded');
    }

    /**
     * @Route("/organisation", name="api_organisation_create", methods={"POST"})
     */
    public function createOrganisations(Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();
        $token = $request->headers->get('authorization');
        $entityManager = $this->getDoctrine()->getManager();
        $auth = $this->authenticator->verifyToken($token, 'ADMINISTRATION');

        $organisation = new Organisations();

        $organisation->setName($payload['name']);

        $organisationColor = $this->colorService->getRandomColor();

        $organisation->setColor($organisationColor);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($organisation);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'organisations_created');
    }

    /**
     * @Route("/organisation/{orgId}", name="api_organisation_update", methods={"PUT"})
     * @param int $orgId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateOrganisation(int $orgId, Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();
        $token = $request->headers->get('authorization');
        $entityManager = $this->getDoctrine()->getManager();

        $auth = $this->authenticator->verifyToken($token, 'ADMINISTRATION');

        /** @var $organisation Organisations */
        $organisation = $this->organisationsRepository()->find($orgId);
        if(!$organisation) throw new InvalidRequestException();

        if(isset($payload['color'])) {
            $color = $this->colorsRepository()->find($payload['color']);

            if($color) $organisation->setColor($color);
        }

        if(isset($payload['name']) && $payload['name'] !== '') {
            $organisation->setName($payload['name']);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($organisation);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'organisation_updated');
    }

    /**
     * @Route("/organisation/{orgId}", name="api_organisation_delete", methods={"DELETE"})
     * @param int $orgId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteOrganisation(int $orgId, Request $request)
    {
        $data = [];
        $token = $request->headers->get('authorization');
        $entityManager = $this->getDoctrine()->getManager();

        /** @var $organisation Organisations */
        $organisation = $this->organisationsRepository()->find($orgId);
        if(!$organisation) throw new InvalidRequestException();

        $entityManager->remove($organisation);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'organisation_deleted');

    }

    /**
     * @Route("/organisation/{orgId}/projects", name="api_organisation_get_projects", methods={"GET"})
     * @param int $orgId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getOrganisationProjects(int $orgId, Request $request)
    {
        $data = [
            'projects' => []
        ];
        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);
        $organisation = $this->authenticator->checkOrganisationPermission($auth, $orgId);

        $projects = $organisation->getProjects();

        foreach($projects as $project) {
            $projectData = [];
            if($project->getClosed() && !$auth->getUser()->getUserPermissions()->getAdministration()) {

                if($project->getProjectUsers()->contains($auth->getUser())) {
                    $projectData = [
                        'name' => $project->getName(),
                        'id' => $project->getId(),
                        'closed' => $project->getClosed()
                    ];
                    array_push($data['projects'], $projectData);
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

    /**
     * @Route("/organisation/{orgId}/users", name="api_organisation_get_users", methods={"GET"})
     * @param int $orgId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getOrganisationUsers(int $orgId, Request $request)
    {
        $data = [
            'users' => []
        ];
        $token = $request->headers->get('authorization');
        $auth = $this->authenticator->verifyToken($token);

        /** @var $organisation Organisations */
        $organisation = $this->organisationsRepository()->find($orgId);
        if(!$organisation) throw new InvalidRequestException();
        $users = $organisation->getUsers();

        foreach($users as $user) {
            if(!$user->getActive()) continue;

            $data['users'][] = [
              'id' => $user->getId(),
              'firstname' => $user->getFirstname(),
              'lastname' => $user->getLastname()
            ];
        }

        return $this->responseManager->successResponse($data, 'users_loaded');
    }
}