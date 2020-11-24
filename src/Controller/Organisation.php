<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Entity\Organisations;
use App\Entity\Projects;
use App\Security\TaskooAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Organisation extends AbstractController
{
    /**
     * @Route("/organisation/get", name="api_organisation_get")
     */
    public function getOrganisations(Request $request, TaskooAuthenticator $authenticator)
    {
        $success = false;
        $message = null;
        $data = null;

        //if its the actual get request
        if ($request->getMethod() == 'GET') {
            $token = $request->headers->get('authorization');
            $userId = $request->headers->get('user');

            $entityManager = $this->getDoctrine()->getManager();

            //authentification process
            $auth = $authenticator->checkUserAuth($userId, $token);

            //if user is admin, return every organisation
            if (isset($auth['type']) && $auth['type'] == 'is_admin') {
                $organisations = $this->getDoctrine()->getRepository(Organisations::class)->findAll();

                $data = [];
                foreach($organisations as $key=>&$org) {
                    $data[$key]['name'] = $org->getName();
                    $data[$key]['id'] = $org->getId();
                }

                $success = true;

                //else return organisations for user
            } elseif ($auth['user']) {
                $organisations = $auth['user']->getOrganisations();

                print_r($organisations->first()->getName());

                $data = $organisations->map(function ($org) {
                    return [
                        'name' => $org->getName(),
                        'id' => $org->getId()
                    ];
                })->toArray();
                $success = true;

            } else {
                $message = 'permission_denied';
            }
        }



        $response = new JsonResponse([
            'success' => $success,
            'message' => $message,
            'organisations' => $data
        ]);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", "GET");
        $response->headers->set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, User");
        return $response;
    }
}