<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Entity\Team;
use App\Exception\InvalidRequestException;
use App\Service\TaskooColorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Teams extends TaskooApiController
{
    /**
     * @Route("/team", name="api_team_get", methods={"GET"})
     */
    public function getTeams(Request $request)
    {
        $data = [];

        $this->authenticator->verifyToken($request, 'ADMINISTRATION');
        $teams = $this->teamRepository()->findAll();

        foreach($teams as $key=>$team) {
            $data['teams'][$key] = [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'projectCount' => $team->getProjects()->count(),
                'userCount' => $team->getUsers()->count()
            ];

            if($team->getColor()) {
                $data['teams'][$key]['color'] = [
                    'id' => $team->getColor()->getId(),
                    'hexCode' => $team->getColor()->getHexCode()
                ];
            }
        }

        return $this->responseManager->successResponse($data, 'teams_loaded');
    }

    /**
     * @Route("/team", name="api_team_create", methods={"POST"})
     */
    public function createTeam(Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();
        $this->authenticator->verifyToken($request, 'ADMINISTRATION');

        $team = new Team();
        $team->setName($payload['name']);
        $teamColor = $this->colorService->getRandomColor();
        $team->setColor($teamColor);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($team);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'team_created');
    }

    /**
     * @Route("/team/{orgId}", name="api_team_update", methods={"PUT"})
     * @param int $orgId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateteam(int $orgId, Request $request)
    {
        $data = [];
        $payload = json_decode($request->getContent(), true);
        if(!$payload) throw new InvalidRequestException();

        $this->authenticator->verifyToken($request, 'ADMINISTRATION');

        /** @var $team Team */
        $team = $this->teamRepository()->find($orgId);
        if(!$team) throw new InvalidRequestException();

        if(isset($payload['color'])) {
            $color = $this->colorsRepository()->find($payload['color']);

            if($color) $team->setColor($color);
        }

        if(isset($payload['name']) && $payload['name'] !== '') {
            $team->setName($payload['name']);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($team);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'team_updated');
    }

    /**
     * @Route("/team/{orgId}", name="api_team_delete", methods={"DELETE"})
     * @param int $orgId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteTeam(int $orgId, Request $request)
    {
        $data = [];
        $auth = $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);
        $entityManager = $this->getDoctrine()->getManager();

        /** @var $team Team */
        $team = $this->teamRepository()->find($orgId);
        if(!$team) throw new InvalidRequestException();

        $entityManager->remove($team);
        $entityManager->flush();

        return $this->responseManager->successResponse($data, 'team_deleted');

    }

    /**
     * @Route("/team/{orgId}/projects", name="api_team_get_projects", methods={"GET"})
     * @param int $orgId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTeamProjects(int $orgId, Request $request)
    {
        $data = [
            'projects' => [],
            'favorites'=> []
        ];
        $auth = $this->authenticator->verifyToken($request);
        $team = $this->authenticator->checkteamPermission($auth, $orgId);

        $projects = $team->getProjects();

        foreach($projects as $project) {
            $projectData = [];
            if($project->getClosed() && !$auth->getUser()->getUserPermissions()->getAdministration()) {
                if($project->getProjectUsers()->contains($auth->getUser())) {
                    $projectData = $project->getProjectMainData();
                    $projectData['team'] = $project->getteam()->getteamData();
                    array_push($data['projects'], $projectData);
                } else {
                    continue;
                }
            } else {
                $projectData = $project->getProjectMainData();
                $projectData['team'] = $project->getteam()->getteamData();
                array_push($data['projects'], $projectData);
            }
        }

        //load users favorite projects
        $favorites = $auth->getUser()->getFavorites();

        foreach($favorites as $favorite) {
            $project = $favorite->getProject();

            $projectData = $project->getProjectMainData();
            $projectData['position'] = $favorite->getPosition();
            $projectData['team'] = $project->getteam()->getteamData();
            $projectData['favoriteId'] = $favorite->getId();
            array_push($data['favorites'], $projectData);
        }

        usort($data['favorites'], function($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $this->responseManager->successResponse($data, 'team_projects_loaded');

    }

    /**
     * @Route("/team/{orgId}/users", name="api_team_get_users", methods={"GET"})
     * @param int $orgId
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTeamUsers(int $orgId, Request $request)
    {
        $data = [
            'users' => []
        ];
        $auth = $this->authenticator->verifyToken($request);

        /** @var $team Team */
        $team = $this->teamRepository()->find($orgId);
        if(!$team) throw new InvalidRequestException();
        $users = $team->getUsers();

        foreach($users as $user) {
            if(!$user->getActive()) continue;

            $data['users'][] = [
              'id' => $user->getId(),
              'firstname' => $user->getFirstname(),
              'lastname' => $user->getLastname()
            ];
        }

        return $this->responseManager->successResponse($data, 'team_users_loaded');
    }
}