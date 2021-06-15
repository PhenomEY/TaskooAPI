<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Entity\TeamRole;
use App\Exception\InvalidRequestException;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\LazyCriteriaCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TeamRoles extends TaskooApiController
{
    /**
     * @Route("/teamroles", name="api_roles_get", methods={"GET"})
     */
    public function loadRoles(Request $request)
    {
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);
        $roleCriteria = new Criteria();
        $roleCriteria->orderBy(['priority' => Criteria::DESC]);

        /** @var LazyCriteriaCollection $roles */
        $roles = $this->teamRolesRepository()->matching($roleCriteria);

        $data = [];
        /** @var TeamRole $role */
        foreach($roles->getValues() as $role) {
            $data['teamroles'][] = [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'priority' => $role->getPriority()
            ];
        }

        return $this->responseManager->successResponse($data, 'roles_loaded');
    }

    /**
     * @Route("/teamroles/{roleId}", name="api_roles_update", methods={"PUT"})
     */
    public function updateRole(int $roleId, Request $request)
    {
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);
        $payload = $request->toArray();

        /** @var TeamRole $role */
        $role = $this->teamRolesRepository()->find($roleId);
        if(!$role) throw new InvalidRequestException();

        $role->setName($payload['name']);
        $role->setPriority($payload['priority']);

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($role);
        $manager->flush();

        return $this->responseManager->successResponse([], 'role_updated');
    }

    /**
     * @Route("/teamroles", name="api_roles_create", methods={"POST"})
     */
    public function createRole(Request $request)
    {
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);
        $payload = $request->toArray();

        $role = new TeamRole();
        $role->setName($payload['name']);
        $role->setPriority($payload['priority']);
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($role);
        $manager->flush();

        return $this->responseManager->successResponse([], 'role_created');
    }

    /**
     * @Route("/teamroles/{roleId}", name="api_roles_update", methods={"DELETE"})
     */
    public function deleteRole(int $roleId, Request $request)
    {
        $this->authenticator->verifyToken($request, $this->authenticator::PERMISSIONS_ADMINISTRATION);

        $role = $this->teamRolesRepository()->find($roleId);
        if(!$role) throw new InvalidRequestException();

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($role);
        $manager->flush();

        return $this->responseManager->successResponse([], 'role_deleted');
    }
}