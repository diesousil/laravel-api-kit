<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ApiResponseException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        if (!config('app.debug')) {
            $this->reportable(function (\Throwable $e) {
                if (app()->bound('sentry')) {
                    app('sentry')->captureException($e);
                }
            });
        }
    }

    public function render($request, \Throwable $exception)
    {
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($exception instanceof ApiResponseException) {
            return response()->json($exception->getApiResponse(), $exception->getCode());
        }

        if ($exception instanceof ValidationException) {
            return response()->json(
                ['errors' => $exception->errors()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($exception instanceof NotFoundHttpException ||
            $exception instanceof ModelNotFoundException) {

            return response()->json([
                'success' => false,
                'message' => 'Record not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($exception instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        if (!config('app.debug')) {
            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro inesperado, tente novamente mais tarde!',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return parent::render($request, $exception);
    }
}
