<?php
namespace App\Controller;

mb_http_output('UTF-8');

use App\Api\TaskooApiController;
use App\Entity\Color;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Colors extends TaskooApiController
{
    /**
     * @Route("/colors", name="api_colors_get", methods={"GET"})
     */
    public function getOrganisations(Request $request)
    {
        $data = [];
        $token = $request->headers->get('authorization');
        $entityManager = $this->getDoctrine()->getManager();

        //check if auth token got sent
        if(isset($token)) {
            $auth = $this->authenticator->checkUserAuth($token);

            if(isset($auth['user'])) {
                $colorsRepository = $this->colorsRepository();

                $colors = $colorsRepository->getAvailableColors();

                $data['colors'] = $colors;

                return $this->responseManager->successResponse($data, 'colors_loaded');
            }

        }

        return $this->responseManager->forbiddenResponse();
    }
}