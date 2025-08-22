<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseApiController extends BaseController
{
    protected function successResponse($data = null, $message = null, int $status = 200): JsonResponse
    {
        $response = [];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = ['message' => $message];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function resourceResponse(JsonResource $resource, $message = null): JsonResponse
    {
        $data = $resource->toArray(request());

        return $this->successResponse($data, $message);
    }

    protected function collectionResponse(ResourceCollection $collection, $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $collection->items(),
            'meta' => $this->getPaginationMeta($collection),
        ])->setStatusCode(200);
    }

    private function getPaginationMeta($collection): array
    {
        if (method_exists($collection->resource, 'currentPage')) {
            $paginator = $collection->resource;

            return [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ];
        }

        return [
            'total' => $collection->count()
        ];
    }
}
