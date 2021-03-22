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

        $auth = $this->authenticator->verifyToken($token);

        $colorsRepository = $this->colorsRepository();

        $colors = $colorsRepository->getAvailableColors();

        $data['colors'] = $colors;

        return $this->responseManager->successResponse($data, 'colors_loaded');

    }
}