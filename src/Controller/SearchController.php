<?php

namespace Taskoo\Controller;

use Taskoo\Api\TaskooApiController;
use Taskoo\Service\TaskooSearchService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends TaskooApiController
{
    /**
     * @Route("/search/{searchTerm}", name="api_search", methods={"GET"})
     * @param String $searchTerm
     * @param Request $request
     */
    public function search($searchTerm, Request $request, TaskooSearchService $searchService)
    {
        $auth = $this->authenticator->verifyToken($request);
        $limit = $request->get('l');
        $offset = $request->get('o');
        $type = $request->get('t');

        $results = $searchService->search($searchTerm, $auth, $limit, $offset, $type);

        return $this->responseManager->successResponse($this->serializer->normalize($results), 'success');
    }
}