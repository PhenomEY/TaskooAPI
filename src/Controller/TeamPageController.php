<?php declare(strict_types=1);
namespace Taskoo\Controller;

use Taskoo\Api\ApiController;
use Taskoo\Entity\Team;
use Taskoo\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TeamPageController extends ApiController
{
    /**
     * @Route("/teampage", name="api_teampage_get", methods={"GET"})
     */
    public function getTeamPage(Request $request)
    {
        $auth = $this->authenticator->verifyToken($request);
        $teams = $auth->getUser()->getTeams();
        $data = [];

        /** @var Team $team */
        foreach($teams as $key => $team) {
            $data['teams'][$key] = $team->getTeamData();

            $users = $this->userRepository()->getSortedTeamUsers($team);

            /** * @var User $user */
            foreach($users as $index => $user) {
                if($user->getTeamRole()) {
                    $data['teams'][$key]['columns'][$user->getTeamRole()->getPriority()][] = $user->getUserData();
                } else {
                    $data['teams'][$key]['columns'][0][] = $user->getUserData();
                }

            }
        }

        return $this->responseManager->successResponse($data, 'team_page_loaded');
    }
}

