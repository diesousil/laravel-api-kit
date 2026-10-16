<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\Resources\Resource;
use App\Models\Model;
use InvalidArgumentException;

abstract class Controller
{
    protected function getResourceClass(): ?string
    {
        return null;
    }

    private function validateResourceClass(?string $resourceClass)
    {
        if ($resourceClass != null && !is_subclass_of($resourceClass, JsonResource::class)) {
            throw new InvalidArgumentException(
                "A classe {$this->resource} deve estender JsonResource."
            );
        }
    }

    private function applyResource(mixed $data)
    {
        $resourceClass = $this->getResourceClass();

        if (!$resourceClass || is_bool($data)) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ], Response::HTTP_OK);
        }

        $this->validateResourceClass($resourceClass);

        if ($data instanceof Model) {
            return new $resourceClass($data);
        } else {
            return $resourceClass::collection($data)->additional(['success' => true]);
        }

    }

    public function success(
        mixed $data
    ): JsonResponse|JsonResource {

        if ($data == null) {
            throw new NotFoundHttpException('Record not found.');
        }

        return $this->applyResource($data);
    }

    public function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => $message,
        ], $statusCode);
    }
}
