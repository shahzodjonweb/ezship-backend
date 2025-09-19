<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException as LaravelAuthException;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ConfigurationException::class,
        ValidationException::class,
        AuthenticationException::class,
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Handle our custom exceptions
        if ($exception instanceof ConfigurationException ||
            $exception instanceof ValidationException ||
            $exception instanceof AuthenticationException) {
            return $exception->render($request);
        }

        // Handle API requests with JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions with proper status codes
     *
     * @param Request $request
     * @param Throwable $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException($request, Throwable $exception)
    {
        $statusCode = 500;
        $message = 'Server Error';
        $errorType = 'server_error';

        if ($exception instanceof ModelNotFoundException) {
            $statusCode = 404;
            $message = 'Resource not found';
            $errorType = 'not_found';
        } elseif ($exception instanceof NotFoundHttpException) {
            $statusCode = 404;
            $message = 'Endpoint not found';
            $errorType = 'not_found';
        } elseif ($exception instanceof MethodNotAllowedHttpException) {
            $statusCode = 405;
            $message = 'Method not allowed';
            $errorType = 'method_not_allowed';
        } elseif ($exception instanceof LaravelAuthException) {
            $statusCode = 401;
            $message = 'Unauthenticated';
            $errorType = 'authentication_error';
        } elseif ($exception instanceof LaravelValidationException) {
            $statusCode = 422;
            $message = 'Validation failed';
            $errorType = 'validation_error';
            
            return response()->json([
                'success' => false,
                'message' => $message,
                'error_type' => $errorType,
                'errors' => $exception->errors(),
                'status_code' => $statusCode
            ], $statusCode);
        } elseif (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'Error';
        } else {
            // For generic exceptions in production, don't expose details
            if (!config('app.debug')) {
                $message = 'An error occurred while processing your request';
            } else {
                $message = $exception->getMessage();
            }
        }

        return response()->json([
            'success' => false,
            'error' => $message,
            'error_type' => $errorType,
            'status_code' => $statusCode
        ], $statusCode);
    }
}
