<?php

use App\Exceptions\AccountLockedException;
use App\Exceptions\CarNotAvailableException;
use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\PaymentAlreadyPaidException;
use App\Exceptions\RentalStatusConflictException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Only apply JSON envelope for API routes
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // 422 Validation errors
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'The given data was invalid.',
                    'data'    => $e->errors(),
                ], 422);
            }
        });

        // 401 Unauthenticated
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated.',
                    'data'    => null,
                ], 401);
            }
        });

        // 403 Forbidden
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Forbidden.',
                    'data'    => null,
                ], 403);
            }
        });

        // 404 Not Found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Not Found.',
                    'data'    => null,
                ], 404);
            }
        });

        // 422 Business Logic — CarNotAvailableException
        $exceptions->render(function (CarNotAvailableException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'data'    => null,
                ], 422);
            }
        });

        // 422 Business Logic — RentalStatusConflictException
        $exceptions->render(function (RentalStatusConflictException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'data'    => null,
                ], 422);
            }
        });

        // 422 Business Logic — PaymentAlreadyPaidException
        $exceptions->render(function (PaymentAlreadyPaidException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'data'    => null,
                ], 422);
            }
        });

        // 423 Business Logic — AccountLockedException
        $exceptions->render(function (AccountLockedException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'data'    => null,
                ], 423);
            }
        });

        // 403 Business Logic — EmailNotVerifiedException
        $exceptions->render(function (EmailNotVerifiedException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'data'    => null,
                ], 403);
            }
        });
    })->create();
