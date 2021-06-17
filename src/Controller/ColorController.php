<?php
namespace Taskoo\Controller;

mb_http_output('UTF-8');

use Taskoo\Api\TaskooApiController;
use Taskoo\Entity\Color;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ColorController extends TaskooApiController
{
    /**
     * @Route("/colors", name="api_colors_get", methods={"GET"})
     */
    public function getColors(Request $request)
    {
        $data = [];
        $entityManager = $this->getDoctrine()->getManager();
        $auth = $this->authenticator->verifyToken($request);

        $colorsRepository = $this->colorsRepository();

        $colors = $colorsRepository->getAvailableColors();

        $data['colors'] = $colors;

        return $this->responseManager->successResponse($data, 'colors_loaded');

    }
}