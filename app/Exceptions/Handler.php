<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // API：404 Not Found
        if (
            $request->is('api/*')
            && $exception instanceof ModelNotFoundException
        ) {
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。',
            ], 404);
        }

        // API：403 Forbidden
        if (
            $request->is('api/*')
            && $exception instanceof AuthorizationException
        ) {
            return response()->json([
                'error' => 'この操作を実行する権限がありません。',
            ], 403);
        }

        // API：401 Unauthorized
        if (
            $request->is('api/*')
            && $exception instanceof AuthenticationException
        ) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // API：422 Unprocessable Entity
        if (
            $request->is('api/*')
            && $exception instanceof ValidationException
        ) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        return parent::render($request, $exception);
    }
}
