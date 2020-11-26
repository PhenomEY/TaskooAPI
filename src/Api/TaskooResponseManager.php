<?php

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;


class TaskooResponseManager {


    public function notFoundResponse() {
        $response = new JsonResponse([
            'success' => false,
            'message' => 'not_found'
        ]);
        $response->setStatusCode(404);

        return $response;
    }

    public function forbiddenResponse() {
        $response = new JsonResponse([
            'success' => false,
            'message' => 'permission_denied'
        ]);
        $response->setStatusCode(403);

        return $response;
    }

    public function badRequestResponse() {
        $response = new JsonResponse([
            'success' => false
        ]);
        $response->setStatusCode(400);

        return $response;
    }

    public function unauthorizedResponse() {
        $response = new JsonResponse([
            'success' => false
        ]);
        $response->setStatusCode(401);

        return $response;
    }

    public function successResponse(array $data, string $message) {
        $data['success'] = true;
        $data['message'] = $message;

        $response = new JsonResponse($data);
        $response->setStatusCode(200);

        return $response;
    }


}